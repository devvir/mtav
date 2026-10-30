<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\LotteryRunStatus;
use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Event;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectService
{
    public function __construct(
        private PlanService $plans,
        private NotificationService $notifications,
    ) {
    }

    /**
     * Start a project with its lottery and an empty plan. Projects started by a
     * superadmin are approved from the outset; anyone else becomes its first admin.
     *
     * @param array{name: string, description: string, organization?: ?string} $data
     */
    public function create(User $creator, array $data): Project
    {
        $project = DB::transaction(function () use ($creator, $data) {
            $project = Project::create([
                ...$data,
                'created_by'  => $creator->id,
                'approved_at' => $creator->isSuperadmin() ? now() : null,
                'approved_by' => $creator->isSuperadmin() ? $creator->id : null,
            ]);

            Event::create([
                'project_id'   => $project->id,
                'creator_id'   => $creator->id,
                'type'         => EventType::LOTTERY,
                'title'        => __('lottery.title'),
                'description'  => __('lottery.default_description'),
                'is_published' => true,
            ]);

            $this->plans->createFor($project);

            if (! $creator->isSuperadmin()) {
                $project->memberships()->create(['user_id' => $creator->id, 'is_admin' => true]);
                $creator->unsetRelation('memberships');
            }

            return $project;
        });

        $this->notifications->send(
            NotificationTarget::GLOBAL,
            NotificationType::RESOURCE_CREATED,
            title: 'notifications.project_created_title',
            message: 'notifications.project_created_message',
            params: ['name' => $project->name],
            url: route('admin.projects.index', ['q' => $project->name]),
            trigger: $creator,
        );

        return $project;
    }

    public function requestApproval(Project $project, User $requester): void
    {
        $project->update(['approval_requested_at' => now()]);

        $this->notifications->send(
            NotificationTarget::GLOBAL,
            NotificationType::APPROVAL_REQUESTED,
            title: 'notifications.approval_requested_title',
            message: 'notifications.approval_requested_message',
            params: ['name' => $project->name, 'requester' => $requester->name],
            url: route('admin.projects.index', ['show' => 'awaiting']),
            trigger: $requester,
        );
    }

    public function approve(Project $project, User $superadmin): void
    {
        $project->update(['approved_at' => now(), 'approved_by' => $superadmin->id]);

        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::PROJECT_APPROVED,
            title: 'notifications.project_approved_title',
            message: 'notifications.project_approved_message',
            params: ['name' => $project->name],
            url: route('projects.show', $project),
            trigger: $superadmin,
            targetId: $project->id,
        );
    }

    /**
     * Its admins delete it: archived when it has a history (kept for audits, gone for
     * everyone but superadmins), else deleted for good. Whether it was archived.
     */
    public function remove(Project $project, User $by): bool
    {
        if (! $project->hasHistory()) {
            $this->purge($project);

            return false;
        }

        $project->forceFill(['archived_by' => $by->id])->save();
        $project->delete();

        return true;
    }

    /** An archived project, back as it was */
    public function restore(Project $project): void
    {
        $project->forceFill(['archived_by' => null])->save();
        $project->restore();
    }

    /** Deleted for good: everything in it, its files and its notifications */
    public function purge(Project $project): void
    {
        DB::transaction(function () use ($project) {
            Notification::where('target', NotificationTarget::PROJECT)->where('target_id', $project->id)->delete();
            $project->forceDelete();
        });
        Storage::disk(Media::DISK)->deleteDirectory("projects/{$project->id}");
    }

    /**
     * How far the project is from its lottery: each step of the setup, done or not.
     *
     * @return array{unit_types: bool, units: bool, families: bool, members: bool, scheduled: bool, lottery: bool}
     */
    public function setupChecklist(Project $project): array
    {
        return [
            'unit_types' => $project->unitTypes()->exists(),
            'units'      => $project->units()->exists(),
            'families'   => $project->families()->exists(),
            'members'    => $project->memberships()->whereNotNull('family_id')->exists(),
            'scheduled'  => (bool) $project->lottery->start_date,
            'lottery'    => $project->activeLotteryRun?->status === LotteryRunStatus::COMPLETED,
        ];
    }
}
