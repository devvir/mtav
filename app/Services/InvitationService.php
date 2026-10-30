<?php

namespace App\Services;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Family;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Project;
use App\Models\User;
use App\Notifications\InvitationNotification;
use App\Notifications\JoinedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * People join a project by invitation: an emailed link, valid for a few days, that
 * creates their account (or adds the project to an existing one). Someone who already has
 * an account also finds it in the app, under its bell, leading to where they join.
 * Someone already taking part with no family needs no invitation to enter one: they are
 * added at once, and told.
 */
class InvitationService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * @param array{email: string, firstname?: ?string, lastname?: ?string} $person
     *
     * @return Invitation|Membership  The invitation sent, or the place of someone added at once
     *
     * @throws ValidationException when the person is already in, or a limit is reached
     */
    public function invite(Project $project, User $inviter, array $person, ?Family $family, bool $asAdmin): Invitation|Membership
    {
        $email = strtolower(trim($person['email']));

        $this->ensureNotAlreadyIn($project, $email, $family, $asAdmin);

        $familyless = $family && ! $asAdmin
            ? $project->memberships()->whereNull('family_id')->whereRelation('user', 'email', $email)->first()
            : null;

        if ($familyless) {
            return $this->addToFamily($familyless, $family, $inviter);
        }

        $this->ensureWithinLimits($project);

        $token = Str::random(48);

        $invitation = DB::transaction(function () use ($project, $inviter, $person, $email, $family, $asAdmin, $token) {
            /** A newer invitation replaces any pending one for the same person */
            $project->invitations()->pending()->where('email', $email)->delete();

            return $project->invitations()->create([
                'family_id'  => $family?->id,
                'is_admin'   => $asAdmin,
                'email'      => $email,
                'firstname'  => $person['firstname'] ?? null,
                'lastname'   => $person['lastname'] ?? null,
                'token_hash' => Invitation::hashToken($token),
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays(config('limits.invitation_days')),
            ]);
        });

        Notification::route('mail', $email)->notify(new InvitationNotification($invitation, $token));

        if ($invitee = User::firstWhere('email', $email)) {
            $this->notifications->send(
                NotificationTarget::PRIVATE,
                NotificationType::INVITATION_RECEIVED,
                title: 'notifications.invitation_received_title',
                message: 'notifications.invitation_received_message',
                params: ['name' => $inviter->name, 'project' => $project->name],
                url: route('projects.index'),
                trigger: $inviter,
                targetId: $invitee->id,
            );
        }

        return $invitation;
    }

    /** What the person inviting is told happened */
    public function outcome(Invitation|Membership $result): string
    {
        return $result instanceof Membership
            ? __('flash.added_to_family', ['name' => $result->user->name, 'family' => $result->family->name])
            : __('flash.invitation_sent', ['email' => $result->email]);
    }

    /**
     * Accept an invitation on behalf of a (new or existing) user.
     */
    public function accept(Invitation $invitation, User $user): Membership
    {
        $membership = DB::transaction(function () use ($invitation, $user) {
            $membership = Membership::firstOrNew([
                'user_id'    => $user->id,
                'project_id' => $invitation->project_id,
            ]);

            // An invitation brings someone into a family, never out of the one they are in
            // (admins move people, on purpose, from the people page)
            $membership->family_id = $membership->family_id ?? $invitation->family_id;
            $membership->is_admin = $invitation->is_admin || $membership->is_admin;
            $membership->save();
            $user->unsetRelation('memberships');

            $invitation->update(['accepted_at' => now()]);

            /** Whoever accepts an emailed link proves they own the address */
            $user->email === $invitation->email && ! $user->hasVerifiedEmail() && $user->markEmailAsVerified();

            return $membership;
        });

        $this->notifications->send(
            NotificationTarget::PROJECT,
            NotificationType::MEMBER_JOINED,
            title: 'notifications.member_joined_title',
            message: $invitation->family ? 'notifications.member_joined_family_message' : 'notifications.member_joined_message',
            params: ['name' => $user->name, 'family' => $invitation->family?->name],
            url: route('people.show', ['project' => $invitation->project_id, 'member' => $user->id]),
            trigger: $user,
            targetId: $invitation->project_id,
        );

        return $membership;
    }

    private function addToFamily(Membership $membership, Family $family, User $by): Membership
    {
        $user = $membership->user;

        DB::transaction(function () use ($membership, $family, $user) {
            $membership->update(['family_id' => $family->id]);
            $membership->project->invitations()->pending()->where('email', $user->email)->delete();
            $user->unsetRelation('memberships');
        });

        if (! $user->is($by)) {
            $this->notifications->send(
                NotificationTarget::PRIVATE,
                NotificationType::ADDED_TO_FAMILY,
                title: 'notifications.added_to_family_title',
                message: 'notifications.added_to_family_message',
                params: ['name' => $by->name, 'family' => $family->name, 'project' => $membership->project->name],
                url: route('projects.show', $membership->project_id),
                trigger: $by,
                targetId: $user->id,
            );
            $user->notify(new JoinedNotification($membership, $by));
        }

        return $membership;
    }

    private function ensureNotAlreadyIn(Project $project, string $email, ?Family $family, bool $asAdmin): void
    {
        $membership = $project->memberships()->whereRelation('user', 'email', $email)->first();

        $alreadyIn = $membership
            && (! $family || $membership->family_id)
            && (! $asAdmin || $membership->is_admin);

        if ($alreadyIn) {
            throw ValidationException::withMessages(['email' => __('validation.already_in_project')]);
        }
    }

    private function ensureWithinLimits(Project $project): void
    {
        $maxMembers = $project->limit('members');
        $perDay = $project->limit('invitations_per_day');

        if ($maxMembers !== null && $project->memberships()->count() + $project->invitations()->pending()->count() >= $maxMembers) {
            throw ValidationException::withMessages(['email' => __('validation.limit_members', ['max' => $maxMembers])]);
        }

        if ($perDay !== null && $project->invitations()->where('created_at', '>', now()->subDay())->count() >= $perDay) {
            throw ValidationException::withMessages(['email' => __('validation.limit_invitations', ['max' => $perDay])]);
        }
    }
}
