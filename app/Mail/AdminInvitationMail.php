<?php

// Copilot - pending review

namespace App\Mail;

use App\Models\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Admin $admin,
        public string $token
    ) {
        // Eager load relationships to prevent lazy loading violations
        $this->admin->loadMissing('projects');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('You\'ve been invited to MTAV as an Administrator'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $confirmationUrl = route('invitation.confirm', [
            'email' => $this->admin->email,
            'token' => $this->token,
        ]);

        $locale = app()->getLocale();
        $view = "emails.{$locale}.admin-invitation";

        return new Content(
            view: $view,
            with: [
                'admin' => $this->admin,
                'projects' => $this->admin->projects,
                'confirmationUrl' => $confirmationUrl,
            ],
        );
    }
}
