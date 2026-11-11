<?php

namespace App\Services;

use App\Enums\LotteryRunStatus;
use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Event;
use App\Models\LotteryRun;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Services\Lottery\AuditService;
use App\Services\Lottery\Contracts\LotteryHost;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\Exceptions\CannotExecuteLotteryException;
use App\Services\Lottery\Exceptions\LotteryLockedException;
use App\Services\Lottery\Exceptions\ParticipantItemMismatchException;
use App\Services\Lottery\LotteryRuns;
use App\Services\Lottery\PreferencesService;
use App\Services\Queue\Workers;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * A project's lottery, from scheduling to the final assignment (and, exceptionally,
 * its invalidation).
 */
class LotteryService implements LotteryHost
{
    public function __construct(
        private PreferencesService $preferences,
        private AuditService $audits,
        private NotificationService $notifications,
        private LotteryRuns $runs,
    ) {
    }

    /**
     * @param array{description?: string, start_date?: ?string} $data
     *
     * @throws LotteryLockedException once the lottery has run (or is running)
     */
    public function schedule(Event $lottery, array $data): void
    {
        if ($lottery->project->lotteryLocked()) {
            throw new LotteryLockedException();
        }

        $lottery->update($data);
    }

    /**
     * Start a run: lock the lottery, record what it decides from, and solve it in the
     * background. Only one run can be active (running or completed) at a time.
     *
     * @param array<string> $options  Confirmations given by the admin
     *
     * @throws CannotExecuteLotteryException|ParticipantItemMismatchException
     */
    public function run(Project $project, User $admin, array $options = []): LotteryRun
    {
        return $this->runs->start(
            $project->lotteryRuns(),
            function () use ($project, $options) {
                Project::whereKey($project->id)->lockForUpdate()->first();

                $this->ensureCanRun($project, $options);
            },
            attributes: ['options' => $options, 'started_by' => $admin->id],
            init: ['admin' => $admin->only('id', 'email')],
        );
    }

    /**
     * What a run decides from: every family's complete ranking, per unit type. In the
     * engine's words each unit type is a group, its families the participants, its units
     * the items.
     */
    public function manifest(LotteryRun $run): LotteryManifest
    {
        $project = $run->project;
        $units = $project->units()->get(['id', 'unit_type_id'])->groupBy('unit_type_id');

        $families = $project->families()->get();
        $rankings = $this->preferences->rankings($families);

        // Ties go by the order families reach the solver: it stays the order they are read in
        $data = $families->groupBy('unit_type_id')
            ->map(fn ($families, $typeId) => [
                'participants' => $families->mapWithKeys(fn ($family) => [$family->id => $rankings[$family->id]])->all(),
                'items'        => ($units[$typeId] ?? collect())->pluck('id')->all(),
            ])
            ->all();

        return new LotteryManifest($run->uuid, $project->id, $run->id, $data, $run->options ?? []);
    }

    /** Each family receives its unit */
    public function assign(LotteryRun $run, ExecutionResult $result): void
    {
        foreach ($result->picks as $familyId => $unitId) {
            Unit::whereKey($unitId)->where('project_id', $run->project_id)->update(['family_id' => $familyId]);
        }
    }

    /** Everyone in the project is told */
    public function completed(LotteryRun $run): void
    {
        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::LOTTERY_COMPLETED,
            title: 'notifications.lottery_completed_title',
            message: 'notifications.lottery_completed_message',
            url: route('lottery.show', $run->project_id),
            trigger: $run->starter,
            targetId: $run->project_id,
        );
    }

    /** Families emptied meanwhile go, and the admin who started it is told */
    public function failed(LotteryRun $run): void
    {
        $this->discardEmptied($run);

        if ($run->started_by) {
            $this->notifications->send(
                NotificationTarget::PRIVATE,
                NotificationType::LOTTERY_FAILED,
                title: 'notifications.lottery_failed_title',
                message: 'notifications.lottery_failed_message',
                url: route('lottery.show', $run->project_id),
                targetId: $run->started_by,
            );
        }
    }

    /**
     * Undo a completed run, leaving a reason that everyone in the project can read. Checked
     * with the run held still: of two admins sending it at once, the second finds it undone
     * (409), and nobody is told twice.
     */
    public function invalidate(LotteryRun $run, User $admin, string $reason): void
    {
        DB::transaction(function () use ($run, $admin, $reason) {
            $held = LotteryRun::whereKey($run->id)->lockForUpdate()->first();

            abort_unless($held?->status === LotteryRunStatus::COMPLETED, Response::HTTP_CONFLICT);

            $run->project->units()->update(['family_id' => null]);

            $run->update([
                'status'              => LotteryRunStatus::INVALIDATED,
                'invalidated_by'      => $admin->id,
                'invalidated_at'      => now(),
                'invalidation_reason' => $reason,
            ]);

            $this->audits->record($run->id, LotteryAuditType::INVALIDATE, [
                'admin'  => $admin->only('id', 'email'),
                'reason' => $reason,
            ]);
        });

        $this->discardEmptied($run);

        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::LOTTERY_INVALIDATED,
            title: 'notifications.lottery_invalidated_title',
            message: 'notifications.lottery_invalidated_message',
            params: ['name' => $admin->name, 'reason' => $reason],
            url: route('lottery.show', $run->project_id),
            trigger: $admin,
            targetId: $run->project_id,
        );
    }

    /** Families left with nobody while the lottery held them still, now that it lets go */
    private function discardEmptied(LotteryRun $run): void
    {
        $run->project->families()->get()->each(app(FamilyService::class)->discardIfEmpty(...));
    }

    /**
     * Refuses a run the lottery may not start, and one where a type's units and families
     * differ in number, until the admin confirms it.
     *
     * @throws CannotExecuteLotteryException|ParticipantItemMismatchException
     */
    private function ensureCanRun(Project $project, array $options): void
    {
        $lottery = $project->lottery;
        $families = $project->families()->count();
        $maxFamilies = $project->limit('families');

        match (true) {
            $project->activeLotteryRun()->exists()            => throw CannotExecuteLotteryException::because('already_run'),
            ! Workers::running()                              => throw CannotExecuteLotteryException::because('no_worker'),
            ! $lottery->start_date                            => throw CannotExecuteLotteryException::because('not_scheduled'),
            $lottery->start_date->isFuture()                  => throw CannotExecuteLotteryException::because('not_due'),
            $families < 2                                     => throw CannotExecuteLotteryException::because('too_few_families'),
            $maxFamilies !== null && $families > $maxFamilies => throw CannotExecuteLotteryException::because('too_large', ['max' => $maxFamilies]),
            default                                           => null,
        };

        if (in_array(ParticipantItemMismatchException::OPTION, $options, true)) {
            return;
        }

        $mismatches = $project->unitTypes()->withCount('units', 'families')->get()
            ->filter(fn ($type) => $type->units_count !== $type->families_count)
            ->map(fn ($type) => [
                'group_id'           => $type->id,
                'group_name'         => $type->name,
                'items_count'        => $type->units_count,
                'participants_count' => $type->families_count,
            ]);

        if ($mismatches->isNotEmpty()) {
            throw new ParticipantItemMismatchException($mismatches);
        }
    }
}
