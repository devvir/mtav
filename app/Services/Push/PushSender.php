<?php

namespace App\Services\Push;

use App\Models\PushSubscription;

/** Hands a notification to a browser's push service */
interface PushSender
{
    /**
     * @param array{title: string, message: string, url: string, tag: string} $payload
     *
     * @return bool Whether the subscription still stands (false: the browser dropped it)
     */
    public function send(PushSubscription $to, array $payload): bool;
}
