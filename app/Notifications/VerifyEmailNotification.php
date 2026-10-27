<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

/**
 * Confirms an email address: the one given at sign-up, or a new one (sent to the
 * new address, and taking effect once confirmed).
 */
class VerifyEmailNotification extends VerifyEmail
{
    public function __construct(public ?string $newEmail = null)
    {
    }

    public function toMail($notifiable): MailMessage
    {
        /** @var User $notifiable */
        $email = $this->newEmail ?? $notifiable->email;

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id'   => $notifiable->getKey(),
            'hash' => sha1($email),
        ]);

        return (new MailMessage())
            ->subject(__('emails.verify_email_subject'))
            ->greeting(__('emails.greeting_name', ['name' => $notifiable->firstname]))
            ->line(__($this->newEmail ? 'emails.verify_new_email_intro' : 'emails.verify_email_intro'))
            ->action(__('emails.verify_email_action'), $url)
            ->line(__('emails.verify_email_ignore'));
    }
}
