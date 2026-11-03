<?php

namespace App\Notifications;

use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the deployment's owner what someone reported through the Assistant: what they said,
 * who, and where to answer them (their email). The console keeps every report too.
 */
class NewReportNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Report $report)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $by = $this->report->user;

        return (new MailMessage())
            ->subject(__('emails.new_report_subject', ['app' => config('app.name')]))
            ->greeting(__('emails.greeting_name', ['name' => $notifiable->firstname]))
            ->line(__('emails.new_report_intro', ['name' => $by?->name ?? '', 'project' => $this->report->project?->name ?? '']))
            ->line($this->report->body)
            ->when($by, fn (MailMessage $mail) => $mail->replyTo($by->email, $by->name))
            ->action(__('emails.new_report_action'), route('admin.reports.index'));
    }
}
