<?php

namespace App\Services\Push;

use App\Models\PushSubscription;

/** Hands notifications to browsers' push services: held one by one, then sent together */
interface PushSender
{
    /**
     * A notification for one device, sent on the next flush()
     *
     * @param array{title: string, message: string, url: string, tag: string} $payload
     */
    public function queue(PushSubscription $to, array $payload): void;

    /**
     * Everything held, sent. One device's trouble goes to the error log and stops nobody
     * else's.
     *
     * @return list<PushSubscription> the devices their browsers dropped
     */
    public function flush(): array;
}
