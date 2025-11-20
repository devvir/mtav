<?php

// Copilot - pending review

namespace App\Mail;

use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberInvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Member $member,
        public string $token
    ) {
        // ...
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->member->family->members()->count() > 1
            ? __('You\'ve been invited to join your Family in MTAV!')
            : __('You\'ve been invited to join MTAV!');

        return new Envelope(subject: $subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $confirmationUrl = route('invitation.edit', [
            'email' => $this->member->email,
            'token' => $this->token,
        ]);

        $locale = app()->getLocale();
        $view = "emails.{$locale}.member-invitation";

        return new Content(
            view: $view,
            with: [
                'member'          => $this->member,
                'family'          => $this->member->family,
                'project'         => $this->member->project,
                'confirmationUrl' => $confirmationUrl,
            ],
        );
    }
}
