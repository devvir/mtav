<?php

namespace App\Services;

use App\Enums\EventType;
use App\Enums\LotteryRunStatus;
use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Event;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
            title: __('notifications.project_created_title'),
            message: __('notifications.project_created_message', ['name' => $project->name]),
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
            title: __('notifications.approval_requested_title'),
            message: __('notifications.approval_requested_message', [
                'name'      => $project->name,
                'requester' => $requester->name,
            ]),
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
            title: __('notifications.project_approved_title'),
            message: __('notifications.project_approved_message', ['name' => $project->name]),
            url: route('projects.show', $project),
            trigger: $superadmin,
            targetId: $project->id,
        );
    }

    /**
     * How far the project is from its lottery: each step of the setup, done or not.
     *
     * @return array{unit_types: bool, units: bool, families: bool, members: bool, balanced: bool, scheduled: bool, lottery: bool}
     */
    public function setupChecklist(Project $project): array
    {
        return [
            'unit_types' => $project->unitTypes()->exists(),
            'units'      => $project->units()->exists(),
            'families'   => $project->families()->exists(),
            'members'    => $project->memberships()->whereNotNull('family_id')->exists(),
            'balanced'   => $this->balanced($project),
            'scheduled'  => (bool) $project->lottery->start_date,
            'lottery'    => $project->activeLotteryRun?->status === LotteryRunStatus::COMPLETED,
        ];
    }

    /** Every type has as many units as families (and there is at least one type) */
    private function balanced(Project $project): bool
    {
        $types = $project->unitTypes()->withCount('units', 'families')->get();

        return $types->isNotEmpty()
            && $types->every(fn ($type) => $type->units_count === $type->families_count);
    }
}
