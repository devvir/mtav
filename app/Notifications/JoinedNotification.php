<?php

namespace App\Notifications;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms, by email, a place someone now has: in a project whose admins accepted them, or
 * in a family someone added them to. Nothing is left to accept.
 */
class JoinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Membership $membership, public ?User $addedBy = null)
    {
        $this->afterCommit();
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $membership = $this->membership->loadMissing('project', 'family');
        $intoFamily = (bool) $this->addedBy;

        $params = [
            'project' => $membership->project->name,
            'family'  => $membership->family?->name,
            'name'    => $this->addedBy?->name,
        ];

        return (new MailMessage())
            ->subject(__($intoFamily ? 'emails.joined_family_subject' : 'emails.joined_project_subject', $params))
            ->greeting(__('emails.greeting_name', ['name' => $notifiable->firstname]))
            ->line(__($intoFamily ? 'emails.joined_family' : 'emails.joined_project', $params))
            ->action(__('emails.joined_action'), route('projects.show', $membership->project_id));
    }
}
