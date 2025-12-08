<?php

namespace App\Listeners;

use App\Events\UserRegistration;
use App\Models\Admin;
use App\Models\Member;
use App\Notifications\AdminInvitationNotification;
use App\Notifications\MemberInvitationNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class SendUserInvitation implements ShouldQueueAfterCommit
{
    public function handle(UserRegistration $event): void
    {
        $user = $event->user;
        $token = $event->token;
        $appUrl = $event->appUrl;

        // Send notification based on user type
        if ($user instanceof Admin) {
            $user->notify(new AdminInvitationNotification($token, $appUrl));
        } elseif ($user instanceof Member) {
            $user->notify(new MemberInvitationNotification($token, $appUrl));
        }
    }
}
