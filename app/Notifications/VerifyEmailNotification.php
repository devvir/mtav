<?php

namespace App\Notifications;

use App\Notifications\Concerns\LocalizableEmail;
use App\Notifications\Contracts\LocalizedEmail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Auth\User;

class VerifyEmailNotification extends VerifyEmail implements LocalizedEmail
{
    use LocalizableEmail;

    public function template(): string
    {
        return 'verify-email';
    }

    public function subjectKey(User $notifiable): string
    {
        return 'verify_email';
    }

    public function data(User $notifiable): array
    {
        return [
            'url' => $this->verificationUrl($notifiable),
        ];
    }
}
