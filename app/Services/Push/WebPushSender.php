<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Web Push, as browsers define it: each push service (Google's, Mozilla's, Apple's) takes
 * the notification encrypted for that browser, signed with the deployment's VAPID keys.
 * Free, no account anywhere.
 */
class WebPushSender implements PushSender
{
    private ?WebPush $push = null;

    public function send(PushSubscription $to, array $payload): bool
    {
        $report = $this->push()->sendOneNotification(
            Subscription::create([
                'endpoint'        => $to->endpoint,
                'contentEncoding' => 'aes128gcm',
                'keys'            => ['p256dh' => $to->public_key, 'auth' => $to->auth_token],
            ]),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ['TTL' => 60 * 60 * 24, 'urgency' => 'normal', 'topic' => substr(md5($payload['tag']), 0, 32)],
        );

        if (! $report->isSuccess() && ! $report->isSubscriptionExpired()) {
            report(new \RuntimeException("Push not delivered: {$report->getReason()}"));
        }

        return ! $report->isSubscriptionExpired();
    }

    private function push(): WebPush
    {
        return $this->push ??= new WebPush(['VAPID' => [
            'subject'    => config('services.push.subject'),
            'publicKey'  => config('services.push.public_key'),
            'privateKey' => config('services.push.private_key'),
        ]]);
    }
}
