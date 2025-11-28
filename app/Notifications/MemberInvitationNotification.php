<?php

namespace App\Notifications;

use App\Models\Member;
use App\Notifications\Concerns\LocalizableEmail;
use App\Notifications\Contracts\LocalizedEmail;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notification;

class MemberInvitationNotification extends Notification implements LocalizedEmail
{
    use LocalizableEmail;

    public function __construct(public string $token)
    {
        // ...
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function template(): string
    {
        return 'member-invitation';
    }

    public function subjectKey(User $notifiable): string
    {
        /** @var Member $notifiable */
        return $notifiable->family->members()->count() > 1
            ? 'member_invitation_family'
            : 'member_invitation';
    }

    public function data(User $notifiable): array
    {
        /** @var Member $notifiable */
        $notifiable->load('family.project');

        return [
            'member'          => $notifiable,
            'family'          => $notifiable->family,
            'project'         => $notifiable->project,
            'confirmationUrl' => route('invitation.edit', [
                'email' => $notifiable->email,
                'token' => $this->token,
            ]),
        ];
    }
}
