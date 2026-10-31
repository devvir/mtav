<?php

namespace App\Notifications;

use App\Models\FamilyJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a family, by email, that someone asks to join it: only its members can answer.
 */
class FamilyRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FamilyJoinRequest $joinRequest)
    {
        $this->afterCommit();
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $joinRequest = $this->joinRequest->loadMissing('family.project', 'user');
        $family = $joinRequest->family;
        $params = ['name' => $joinRequest->user->name, 'family' => $family->name, 'project' => $family->project->name];

        return (new MailMessage())
            ->subject(__('emails.family_request_subject', $params))
            ->greeting(__('emails.greeting_name', ['name' => $notifiable->firstname]))
            ->line(__('emails.family_request', $params))
            ->action(__('emails.family_request_action'), route('families.index', ['project' => $family->project_id, 'family' => $family->id]));
    }
}
