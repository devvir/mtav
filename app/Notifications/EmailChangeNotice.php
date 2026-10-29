<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the address an account uses that someone asked to move it to another one: the
 * owner learns of it even when it was not them.
 */
class EmailChangeNotice extends Notification
{
    public function __construct(public string $newEmail)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('emails.email_change_subject'))
            ->greeting(__('emails.greeting_name', ['name' => $notifiable->firstname]))
            ->line(__('emails.email_change_intro', ['email' => $this->newEmail]))
            ->line(__('emails.email_change_ignore'));
    }
}
