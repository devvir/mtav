<?php

namespace App\Services;

use App\Enums\LotteryRunStatus;
use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Jobs\PublishLotteryRun;
use App\Jobs\RunLottery;
use App\Models\Event;
use App\Models\LotteryRun;
use App\Models\PlatformSetting;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;
use App\Services\Lottery\AuditService;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\Exceptions\CannotExecuteLotteryException;
use App\Services\Lottery\Exceptions\LotteryLockedException;
use App\Services\Lottery\Exceptions\UnitFamilyMismatchException;
use App\Services\Lottery\PreferencesService;
use App\Services\Queue\Workers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A project's lottery, from scheduling to the final assignment (and, exceptionally,
 * its invalidation).
 */
class LotteryService
{
    public function __construct(
        private PreferencesService $preferences,
        private AuditService $audits,
        private NotificationService $notifications,
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
     * @throws CannotExecuteLotteryException|UnitFamilyMismatchException
     */
    public function run(Project $project, User $admin, array $options = []): LotteryRun
    {
        $run = DB::transaction(function () use ($project, $admin, $options) {
            Project::whereKey($project->id)->lockForUpdate()->first();

            $this->ensureCanRun($project, $options);

            $run = $project->lotteryRuns()->create([
                'uuid'       => (string) Str::uuid(),
                'status'     => LotteryRunStatus::RUNNING,
                'solver'     => config('lottery.default'),
                'options'    => $options,
                'started_by' => $admin->id,
                'started_at' => now(),
                // People see it take a while: a lottery over in an instant feels like nothing
                'reveal_at' => now()->addSeconds((int) PlatformSetting::value(PlatformSetting::LOTTERY_SECONDS)),
            ]);

            $this->audits->record($run->id, LotteryAuditType::INIT, [
                'admin'    => $admin->only('id', 'email'),
                'manifest' => $this->manifest($run),
            ]);

            return $run;
        });

        RunLottery::dispatch($run);

        return $run;
    }

    /**
     * What a run decides from: every family's complete ranking, per unit type.
     */
    public function manifest(LotteryRun $run): LotteryManifest
    {
        $project = $run->project;
        $units = $project->units()->get(['id', 'unit_type_id'])->groupBy('unit_type_id');

        $data = $project->families()->get()->groupBy('unit_type_id')
            ->map(fn ($families, $typeId) => [
                'families' => $families->mapWithKeys(fn ($family) => [
                    $family->id => $this->preferences->ranking($family)->pluck('id')->all(),
                ])->all(),
                'units' => ($units[$typeId] ?? collect())->pluck('id')->all(),
            ])
            ->all();

        return new LotteryManifest($run->uuid, $project->id, $run->id, $data, $run->options ?? []);
    }

    /**
     * The assignment found: written now if the run is due to end, else held until it is
     * (until then everyone sees the lottery running)
     */
    public function solved(LotteryRun $run, ExecutionResult $result): void
    {
        if (! $run->reveal_at || $run->reveal_at->isPast()) {
            $this->complete($run, $result);

            return;
        }

        $run->update(['result' => ['picks' => $result->picks, 'orphans' => $result->orphans]]);
        PublishLotteryRun::dispatch($run)->delay($run->reveal_at);
    }

    /**
     * Write the assignment, all at once, and close the run.
     */
    public function complete(LotteryRun $run, ExecutionResult $result): void
    {
        DB::transaction(function () use ($run, $result) {
            foreach ($result->picks as $familyId => $unitId) {
                Unit::whereKey($unitId)->where('project_id', $run->project_id)->update(['family_id' => $familyId]);
            }

            $run->update(['status' => LotteryRunStatus::COMPLETED, 'finished_at' => now()]);
        });

        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::LOTTERY_COMPLETED,
            title: __('notifications.lottery_completed_title'),
            message: __('notifications.lottery_completed_message'),
            url: route('lottery.show', $run->project_id),
            trigger: $run->starter,
            targetId: $run->project_id,
        );
    }

    /**
     * Close a run that could not finish; the lottery can be run again. A run that already
     * finished stays as it is (what failed came after it).
     */
    public function fail(LotteryRun $run, string $userMessage, array $debug = []): void
    {
        if ($run->fresh()?->status !== LotteryRunStatus::RUNNING) {
            return;
        }

        $run->update([
            'status'      => LotteryRunStatus::FAILED,
            'finished_at' => now(),
            'failure'     => $userMessage,
        ]);

        $this->audits->record($run->id, LotteryAuditType::FAILURE, [
            'message' => $userMessage,
            ...$debug,
        ]);

        if ($run->started_by) {
            $this->notifications->send(
                NotificationTarget::PRIVATE,
                NotificationType::LOTTERY_FAILED,
                title: __('notifications.lottery_failed_title'),
                message: $userMessage,
                url: route('lottery.show', $run->project_id),
                targetId: $run->started_by,
            );
        }
    }

    /**
     * Undo a completed run, leaving a reason that everyone in the project can read.
     */
    public function invalidate(LotteryRun $run, User $admin, string $reason): void
    {
        DB::transaction(function () use ($run, $admin, $reason) {
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

        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::LOTTERY_INVALIDATED,
            title: __('notifications.lottery_invalidated_title'),
            message: __('notifications.lottery_invalidated_message', ['name' => $admin->name, 'reason' => $reason]),
            url: route('lottery.show', $run->project_id),
            trigger: $admin,
            targetId: $run->project_id,
        );
    }

    /**
     * @throws CannotExecuteLotteryException|UnitFamilyMismatchException
     */
    private function ensureCanRun(Project $project, array $options): void
    {
        $lottery = $project->lottery;
        $families = $project->families()->count();
        $maxFamilies = $project->limit('families');

        match (true) {
            $project->activeLotteryRun()->exists() => throw CannotExecuteLotteryException::because('already_run'),
            ! Workers::running()                   => throw CannotExecuteLotteryException::because('no_worker'),
            ! $lottery->start_date                 => throw CannotExecuteLotteryException::because('not_scheduled'),
            $lottery->start_date->isFuture()       => throw CannotExecuteLotteryException::because('not_due'),
            $families < 2                          => throw CannotExecuteLotteryException::because('too_few_families'),
            $maxFamilies !== null && $families > $maxFamilies
                    => throw CannotExecuteLotteryException::because('too_large', ['max' => $maxFamilies]),
            default => null,
        };

        if (in_array('mismatch-allowed', $options)) {
            return;
        }

        $mismatches = $project->unitTypes()->withCount('units', 'families')->get()
            ->filter(fn ($type) => $type->units_count !== $type->families_count)
            ->map(fn ($type) => [
                'unit_type_id'   => $type->id,
                'unit_type_name' => $type->name,
                'units_count'    => $type->units_count,
                'families_count' => $type->families_count,
            ]);

        if ($mismatches->isNotEmpty()) {
            throw new UnitFamilyMismatchException($mismatches);
        }
    }
}
