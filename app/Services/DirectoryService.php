<?php

namespace App\Services;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\JoinRequest;
use App\Models\Membership;
use App\Models\Project;
use App\Models\User;
use App\Notifications\JoinedNotification;
use Illuminate\Support\Facades\DB;

/**
 * People find a project in the directory and join it: at once where it is open, or by
 * asking its admins. Either way they enter with no family, until they create theirs or
 * someone of it adds them.
 */
class DirectoryService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    public function join(Project $project, User $user): Membership
    {
        return $this->enter($project, $user);
    }

    public function ask(Project $project, User $user): JoinRequest
    {
        $request = JoinRequest::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $user->id],
            ['declined_at' => null],
        );

        foreach ($project->admins()->get() as $admin) {
            $this->notifications->send(
                NotificationTarget::PRIVATE,
                NotificationType::JOIN_REQUESTED,
                title: 'notifications.join_requested_title',
                message: 'notifications.join_requested_message',
                params: ['name' => $user->name, 'project' => $project->name],
                url: route('manage.invitations.index', $project),
                trigger: $user,
                targetId: $admin->id,
            );
        }

        return $request;
    }

    public function accept(JoinRequest $request): Membership
    {
        $membership = $this->enter($request->project, $request->user);

        $this->notifications->send(
            NotificationTarget::PRIVATE,
            NotificationType::JOIN_ACCEPTED,
            title: 'notifications.join_accepted_title',
            message: 'notifications.join_accepted_message',
            params: ['project' => $request->project->name],
            url: route('projects.show', $request->project),
            targetId: $request->user_id,
        );
        $request->user->notify(new JoinedNotification($membership));

        return $membership;
    }

    public function decline(JoinRequest $request): void
    {
        $request->update(['declined_at' => now()]);

        $this->notifications->send(
            NotificationTarget::PRIVATE,
            NotificationType::JOIN_DECLINED,
            title: 'notifications.join_declined_title',
            message: 'notifications.join_declined_message',
            params: ['project' => $request->project->name],
            url: route('directory.index'),
            targetId: $request->user_id,
        );
    }

    /** In the project, with no family; whatever they had asked is settled by it */
    private function enter(Project $project, User $user): Membership
    {
        $membership = DB::transaction(function () use ($project, $user) {
            $membership = Membership::firstOrCreate(['user_id' => $user->id, 'project_id' => $project->id]);
            $project->joinRequests()->where('user_id', $user->id)->delete();
            $user->unsetRelation('memberships');

            return $membership;
        });

        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::MEMBER_JOINED,
            title: 'notifications.member_joined_title',
            message: 'notifications.member_joined_message',
            params: ['name' => $user->name],
            url: route('people.show', ['project' => $project->id, 'member' => $user->id]),
            trigger: $user,
            targetId: $project->id,
        );

        return $membership;
    }
}
