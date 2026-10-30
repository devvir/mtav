<?php

namespace App\Notifications;

use App\Models\ErrorEntry;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the deployment's owner that the app ran into an error it had not seen before:
 * what, where, and the way to the console's list
 */
class NewErrorNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public ErrorEntry $error)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('emails.new_error_subject', ['app' => config('app.name')]))
            ->greeting(__('emails.greeting_name', ['name' => $notifiable->firstname]))
            ->line(__('emails.new_error_intro'))
            ->line("{$this->error->class}: {$this->error->message}")
            ->line("{$this->error->file}:{$this->error->line}")
            ->action(__('emails.new_error_action'), route('admin.errors.index'));
    }
}
