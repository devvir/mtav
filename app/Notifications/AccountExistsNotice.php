<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Someone signed up with an address that already has an account: its owner is told, by email
 * alone, where to sign in. Sign-up answers the same either way, so it tells nobody else.
 */
class AccountExistsNotice extends Notification
{
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('emails.account_exists_subject', ['app' => config('app.name')]))
            ->greeting(__('emails.greeting_name', ['name' => $notifiable->firstname]))
            ->line(__('emails.account_exists_intro', ['app' => config('app.name')]))
            ->action(__('emails.account_exists_action'), route('login'))
            ->line(__('emails.account_exists_forgot'))
            ->line(__('emails.account_exists_ignore'));
    }
}
