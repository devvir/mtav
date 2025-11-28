<?php

namespace App\Notifications;

use App\Notifications\Concerns\LocalizableEmail;
use App\Notifications\Contracts\LocalizedEmail;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notification;

class AdminInvitationNotification extends Notification implements LocalizedEmail
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
        return 'admin-invitation';
    }

    public function subjectKey(User $notifiable): string
    {
        return 'admin_invitation';
    }

    public function data(User $notifiable): array
    {
        $notifiable->loadMissing('projects');

        return [
            'admin'           => $notifiable,
            'projects'        => $notifiable->projects,
            'confirmationUrl' => route('invitation.edit', [
                'email' => $notifiable->email,
                'token' => $this->token,
            ]),
        ];
    }
}
