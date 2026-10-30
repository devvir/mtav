<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Push\PushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** A notification, to its people's devices: in the background, never holding up the action */
class PushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $notificationId)
    {
    }

    public function handle(PushService $push): void
    {
        ($notification = Notification::find($this->notificationId)) && $push->deliver($notification);
    }
}
