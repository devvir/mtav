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
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Member $member,
        public string $token
    ) {
        // Eager load relationships to prevent lazy loading violations
        $this->member->loadMissing('family.project');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You\'ve been invited to join your family in MTAV!'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $confirmationUrl = route('invitation.confirm', [
            'email' => $this->member->email,
            'token' => $this->token,
        ]);

        $locale = app()->getLocale();
        $view = "emails.{$locale}.member-invitation";

        return new Content(
            view: $view,
            with: [
                'member' => $this->member,
                'family' => $this->member->family,
                'project' => $this->member->family->project,
                'confirmationUrl' => $confirmationUrl,
            ],
        );
    }
}
