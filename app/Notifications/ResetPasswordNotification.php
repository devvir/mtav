<?php

namespace App\Notifications;

use App\Notifications\Concerns\LocalizableEmail;
use App\Notifications\Contracts\LocalizedEmail;
use Illuminate\Foundation\Auth\User;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements LocalizedEmail
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
        return 'reset-password';
    }

    public function subjectKey(User $notifiable): string
    {
        return 'reset_password';
    }

    public function data(User $notifiable): array
    {
        return [
            'url' => url(route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false)),
        ];
    }
}
