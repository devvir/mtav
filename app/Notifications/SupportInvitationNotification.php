<?php

namespace App\Notifications;

use App\Models\SupportInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** The emailed link to a support account. The token exists only here and in the link */
class SupportInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SupportInvitation $invitation, public string $token)
    {
        $this->afterCommit();
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $params = ['app' => config('app.name'), 'inviter' => $this->invitation->inviter?->name ?? config('app.name')];

        return (new MailMessage())
            ->subject(__('emails.support_invitation_subject', $params))
            ->greeting(__('emails.greeting'))
            ->line(__('emails.support_invitation_intro', $params))
            ->action(__('emails.support_invitation_action'), route('support-invitation.show', $this->token))
            ->line(__('emails.invitation_expiry', ['date' => $this->invitation->expires_at->isoFormat('LL')]))
            ->line(__('emails.invitation_ignore'));
    }
}
