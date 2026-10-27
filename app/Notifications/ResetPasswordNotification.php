<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->email]);

        return (new MailMessage())
            ->subject(__('emails.reset_password_subject'))
            ->greeting(__('emails.greeting_name', ['name' => $notifiable->firstname]))
            ->line(__('emails.reset_password_intro'))
            ->action(__('emails.reset_password_action'), $url)
            ->line(__('emails.reset_password_expiry', ['minutes' => config('auth.passwords.users.expire')]))
            ->line(__('emails.reset_password_ignore'));
    }
}
