<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use Http\Client\HttpAsyncClient;
use LogicException;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Http\Client\ClientInterface;
use RuntimeException;
use Throwable;

/**
 * Web Push, as browsers define it: each push service (Google's, Mozilla's, Apple's) takes
 * the notification encrypted for that browser, signed with the deployment's VAPID keys.
 * Free, no account anywhere. What is held goes in one flush: all at once through the
 * asynchronous HTTP client installed (Symfony's, found by php-http/discovery), one after
 * another were there none.
 */
class WebPushSender implements PushSender
{
    private ?WebPush $push = null;

    /** @var array<string, array{to: PushSubscription, payload: string, options: array<string, mixed>}> by endpoint */
    private array $held = [];

    /** @var list<PushSubscription> */
    private array $dropped = [];

    /** How requests leave, one by one and all at once: by default, the clients installed */
    public function __construct(private ?ClientInterface $client = null, private ?HttpAsyncClient $asyncClient = null)
    {
    }

    public function queue(PushSubscription $to, array $payload): void
    {
        // The library throws on keys it cannot encrypt with: no browser gave these
        if (! PushSubscription::areKeys($to->public_key, $to->auth_token)) {
            $this->dropped[] = $to;

            return;
        }

        $held = [
            'to'      => $to,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'options' => ['TTL' => 60 * 60 * 24, 'urgency' => 'normal', 'topic' => substr(md5($payload['tag']), 0, 32)],
        ];
        $this->push()->queueNotification($this->subscription($to), $held['payload'], $held['options']);
        $this->held[$to->endpoint] = $held;
    }

    public function flush(): array
    {
        [$held, $dropped] = [$this->held, $this->dropped];
        [$this->held, $this->dropped] = [[], []];
        if (! $held) {
            return $dropped;
        }
        $settled = [];
        $settle = function (MessageSentReport $report) use ($held, &$settled, &$dropped) {
            $endpoint = $report->getEndpoint();
            $settled[$endpoint] = true;
            if ($report->isSubscriptionExpired()) {
                isset($held[$endpoint]) && $dropped[] = $held[$endpoint]['to'];
            } elseif (! $report->isSuccess()) {
                report(new RuntimeException("Push not delivered: {$report->getReason()}"));
            }
        };

        try {
            $this->sendHeld($settle);
        } catch (Throwable) {
            // Something in one device's keys stopped them all: each of the others on its own
            foreach (array_diff_key($held, $settled) as $one) {
                try {
                    $settle($this->push()->sendOneNotification($this->subscription($one['to']), $one['payload'], $one['options']));
                } catch (Throwable $error) {
                    report($error);
                }
            }
        }

        return $dropped;
    }

    /** @param callable(MessageSentReport): void $settle */
    private function sendHeld(callable $settle): void
    {
        try {
            $this->push()->flushPooled($settle);
        } catch (LogicException) {
            // No asynchronous HTTP client installed (it says so before sending anything)
            foreach ($this->push()->flush() as $report) {
                $settle($report);
            }
        }
    }

    private function subscription(PushSubscription $to): Subscription
    {
        return Subscription::create([
            'endpoint'        => $to->endpoint,
            'contentEncoding' => 'aes128gcm',
            'keys'            => ['p256dh' => $to->public_key, 'auth' => $to->auth_token],
        ]);
    }

    private function push(): WebPush
    {
        return $this->push ??= (new WebPush(['VAPID' => [
            'subject'    => config('services.push.subject'),
            'publicKey'  => config('services.push.public_key'),
            'privateKey' => config('services.push.private_key'),
        ]], client: $this->client, asyncClient: $this->asyncClient));
    }
}
