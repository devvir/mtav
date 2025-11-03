<?php

// Copilot - pending review

namespace App\Listeners;

use App\Events\UserRegistration;
use App\Mail\AdminInvitationMail;
use App\Mail\MemberInvitationMail;
use App\Models\Admin;
use App\Models\Member;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendUserInvitation implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(UserRegistration $event): void
    {
        $user = $event->user;
        $token = $event->token;

        // Determine which Mailable to send based on user type
        if ($user instanceof Admin) {
            $user->load('projects');
            Mail::to($user->email)->send(new AdminInvitationMail($user, $token));
        } elseif ($user instanceof Member) {
            $user->load('family.project');
            Mail::to($user->email)->send(new MemberInvitationMail($user, $token));
        }
    }
}
