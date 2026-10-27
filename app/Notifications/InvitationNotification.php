<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The emailed invitation link. The token exists only here and in the link.
 */
class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invitation $invitation, public string $token)
    {
        $this->afterCommit();
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        $invitation = $this->invitation->loadMissing('project', 'family', 'inviter');

        $params = [
            'project' => $invitation->project->name,
            'family'  => $invitation->family?->name,
            'inviter' => $invitation->inviter?->name ?? config('app.name'),
        ];

        $intro = match (true) {
            $invitation->is_admin && (bool) $invitation->family => 'emails.invitation_admin_family',
            $invitation->is_admin                               => 'emails.invitation_admin',
            default                                             => 'emails.invitation_family',
        };

        return (new MailMessage())
            ->subject(__('emails.invitation_subject', $params))
            ->greeting($invitation->firstname
                ? __('emails.greeting_name', ['name' => $invitation->firstname])
                : __('emails.greeting'))
            ->line(__($intro, $params))
            ->action(__('emails.invitation_action'), route('invitation.show', $this->token))
            ->line(__('emails.invitation_expiry', ['date' => $invitation->expires_at->isoFormat('LL')]))
            ->line(__('emails.invitation_ignore'));
    }
}
