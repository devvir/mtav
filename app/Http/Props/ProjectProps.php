<?php

namespace App\Http\Props;

use App\Enums\ProjectFeature;
use App\Models\Conversation;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use App\Services\ChatService;

/** A project as its pages' frame shows it to someone: its state, their part in it, what they may do */
class ProjectProps
{
    /** @return array<string, mixed> */
    public function of(Project $project, User $user): array
    {
        $family = $user->familyIn($project);
        $manages = $user->can('update', $project);
        // Without guests, until they have a family, finding it is all there is for them
        $inChat = ! $user->needsFamilyIn($project) && $project->hasFeature(ProjectFeature::CHAT) && ((bool) $user->membershipIn($project) || $user->isSuperadmin());

        return [
            'id'                    => $project->id,
            'name'                  => $project->name,
            'is_approved'           => $project->isApproved(),
            'approval_requested_at' => $project->approval_requested_at,
            'family'                => $family ? ['id' => $family->id, 'name' => $family->name] : null,
            // For its admins: the invitations nobody has answered yet (the settings' tab says so)
            'pending_invitations' => $manages ? $project->invitations()->pending()->count() : null,
            // And those asking to join from the directory
            'join_requests' => $manages ? $project->joinRequests()->pending()->count() : null,
            'is_guest'      => $user->isGuestIn($project),
            'allows_guests' => $project->allows_guests,
            // Let in without guests, until they have a family it is all there is for them
            'needs_family' => $user->needsFamilyIn($project),
            'features'     => $project->features(),
            // The conversations with something new for them (the chat's place says so)
            'unread_messages' => $inChat ? Conversation::of($user, $project)->unreadBy($user)->count() : null,
            // Someone's first arrival: the Assistant's conversation, that opens to greet them
            'welcome' => $inChat && $project->hasFeature(ProjectFeature::ASSISTANT) && ChatService::welcomes($user)
                ? Conversation::of($user, $project)->where('is_assistant', true)->value('id')
                : null,
            'can' => [
                'manage' => $manages,
                'invite' => $user->can('invite', [$project, $family]),
                'rank'   => $user->can('rankUnits', $project),
                'upload' => ($project->hasFeature(ProjectFeature::GALLERY) || $project->hasFeature(ProjectFeature::FILES))
                    && $user->can('create', [Media::class, $project]),
                'approve' => $user->can('approve', $project),
                'chat'    => $inChat,
            ],
        ];
    }
}
