<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\PushSubscription;
use App\Services\ChatService;
use App\Services\NotificationService;
use App\Services\Push\PushSender;
use App\Services\Push\WebPushSender;
use Illuminate\Support\Facades\Exceptions;

/**
 * Notifications on people's devices, even with the app closed: each person allows the
 * devices they want; each notification reaches everyone it is for (but whoever caused
 * it), in their own language; a device the browser dropped is forgotten.
 */

/** Delivers nothing: keeps what it was handed, and says which subscriptions are gone */
class FakePushSender implements PushSender
{
    public array $sent = [];

    public array $gone = [];

    public function send(PushSubscription $to, array $payload): bool
    {
        $this->sent[] = ['endpoint' => $to->endpoint, 'user' => $to->user_id, ...$payload];

        return ! in_array($to->endpoint, $this->gone, true);
    }
}

beforeEach(function () {
    config(['services.push.public_key' => 'public', 'services.push.private_key' => 'private']);
    $this->sender = new FakePushSender();
    app()->instance(PushSender::class, $this->sender);

    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project), ['locale' => 'es_UY']);
});

/** As a browser gives them: a P-256 point and a 16-byte secret, in base64url without padding */
function browserKeys(): array
{
    $encode = fn (string $bytes) => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');

    return ['p256dh' => $encode("\x04" . str_repeat('k', 64)), 'auth' => $encode(str_repeat('s', 16))];
}

function allowDevice($test, $user, string $endpoint = 'https://fcm.googleapis.com/device-1')
{
    return $test->as($user)->post(route('push.subscribe'), [
        'endpoint' => $endpoint,
        'keys'     => browserKeys(),
    ]);
}

it('keeps a device someone allows, and forgets it when they turn it off', function () {
    allowDevice($this, $this->member)->assertSessionHasNoErrors();
    expect($this->member->pushSubscriptions()->sole()->endpoint)->toBe('https://fcm.googleapis.com/device-1');

    $this->as($this->member)->delete(route('push.unsubscribe'), ['endpoint' => 'https://fcm.googleapis.com/device-1']);
    expect(PushSubscription::count())->toBe(0);
});

it('gives a device to whoever allows it last (someone else signed in there)', function () {
    allowDevice($this, $this->member);
    allowDevice($this, $this->admin);

    expect(PushSubscription::sole()->user_id)->toBe($this->admin->id);
});

it('never lets anyone turn off someone else\'s device', function () {
    allowDevice($this, $this->member);
    $this->as($this->admin)->delete(route('push.unsubscribe'), ['endpoint' => 'https://fcm.googleapis.com/device-1']);

    expect(PushSubscription::count())->toBe(1);
});

it('takes only secure push addresses', function () {
    allowDevice($this, $this->member, 'http://fcm.googleapis.com/plain')->assertSessionHasErrors('endpoint');
});

it('takes the address of a browser\'s push service: :dataset', function (string $endpoint) {
    allowDevice($this, $this->member, $endpoint)->assertSessionHasNoErrors();
    expect(PushSubscription::sole()->endpoint)->toBe($endpoint);
})->with([
    'Chrome'  => 'https://fcm.googleapis.com/fcm/send/abc',
    'Firefox' => 'https://updates.push.services.mozilla.com/wpush/v2/abc',
    'Safari'  => 'https://web.push.apple.com/abc',
    'Edge'    => 'https://wns2-par02p.notify.windows.com/w/?token=abc',
]);

it('takes no address that would have the server calling elsewhere: :dataset', function (string $endpoint) {
    allowDevice($this, $this->member, $endpoint)->assertSessionHasErrors('endpoint');
    expect(PushSubscription::count())->toBe(0);
})->with([
    'inside the network'         => 'https://10.0.0.5:8443/hook',
    'a host of ours'             => 'https://db/hook',
    'someone\'s site'            => 'https://attacker.example.com/hook',
    'a name that only starts so' => 'https://fcm.googleapis.com.attacker.example.com/hook',
    'behind a user name'         => 'https://fcm.googleapis.com@10.0.0.5/hook',
]);

it('takes only keys a notification can be encrypted with: :dataset', function (array $keys) {
    $this->as($this->member)->post(route('push.subscribe'), [
        'endpoint' => 'https://fcm.googleapis.com/device-1',
        'keys'     => [...browserKeys(), ...$keys],
    ])->assertSessionHasErrors('keys.auth');

    expect(PushSubscription::count())->toBe(0);
})->with([
    'too short'          => [['p256dh' => 'x']],
    'not base64'         => [['p256dh' => '!!!not-a-key!!!']],
    'not a point'        => [['p256dh' => rtrim(strtr(base64_encode("\x03" . str_repeat('k', 64)), '+/', '-_'), '=')]],
    'a secret too short' => [['auth' => 'c2hvcnQ']],
]);

describe('A chat message\'s title on a device', function () {
    beforeEach(function () {
        $this->writer = aMemberOf(aFamily($this->project), ['firstname' => 'Ana']);
        $this->reader = aMemberOf(aFamily($this->project), ['firstname' => 'Beto']);
        $this->third = aMemberOf(aFamily($this->project), ['firstname' => 'Carla']);
        allowDevice($this, $this->reader, 'https://fcm.googleapis.com/reader');
    });

    $writes = function (array $people, ?string $title = null) {
        $chat = app(ChatService::class);
        $chat->send($chat->start($this->project, $this->writer, $people, $title), $this->writer, 'Hello');

        return collect($this->sender->sent)->firstWhere('endpoint', 'https://fcm.googleapis.com/reader')['title'];
    };

    it('says who wrote and the group\'s name', function () use ($writes) {
        expect($writes->call($this, [$this->reader->id, $this->third->id], 'Roof'))->toBe("{$this->writer->name} · Roof");
    });

    it('names a group with no name by who else is in it', function () use ($writes) {
        expect($writes->call($this, [$this->reader->id, $this->third->id]))->toBe("{$this->writer->name} · Carla");
    });

    it('ends on who wrote in a group of just the two of them, with no separator left over', function () use ($writes) {
        $chat = app(ChatService::class);
        $group = $chat->start($this->project, $this->writer, [$this->reader->id, $this->third->id]);
        $chat->leave($group, $this->third);
        $chat->send($group, $this->writer, 'Hello');

        expect(collect($this->sender->sent)->firstWhere('endpoint', 'https://fcm.googleapis.com/reader')['title'])->toBe($this->writer->name);
    });

    it('says only who wrote one on one', function () use ($writes) {
        expect($writes->call($this, [$this->reader->id]))->toBe($this->writer->name);
    });
});

describe('A device in trouble', function () {
    /** A sender that throws for one device */
    function senderThrowingFor(string $endpoint): FakePushSender
    {
        $sender = new class ($endpoint) extends FakePushSender {
            public function __construct(public string $bad)
            {
            }

            public function send(PushSubscription $to, array $payload): bool
            {
                if ($to->endpoint === $this->bad) {
                    throw new ErrorException('Invalid client public key length.');
                }

                return parent::send($to, $payload);
            }
        };
        app()->instance(PushSender::class, $sender);

        return $sender;
    }

    it('stops nobody else\'s notification, and it is told in the error log', function () {
        Exceptions::fake();
        $other = aMemberOf(aFamily($this->project));
        $bad = $this->member->pushSubscriptions()->create(['endpoint' => 'https://fcm.googleapis.com/bad', 'public_key' => 'x', 'auth_token' => 'y']);
        allowDevice($this, $other, 'https://fcm.googleapis.com/other');
        allowDevice($this, $this->admin, 'https://fcm.googleapis.com/admin');
        $sender = senderThrowingFor($bad->endpoint);

        app(NotificationService::class)->send(
            NotificationTarget::PROJECT,
            NotificationType::FORUM_TOPIC,
            'A title',
            'A message',
            targetId: $this->project->id,
        );

        expect(collect($sender->sent)->pluck('endpoint')->sort()->values()->all())
            ->toBe(['https://fcm.googleapis.com/admin', 'https://fcm.googleapis.com/other'])
            ->and(PushSubscription::whereKey($bad->id)->exists())->toBeTrue();
        Exceptions::assertReported(ErrorException::class);
    });

    it('stops nobody else\'s chat message', function () {
        Exceptions::fake();
        $other = aMemberOf(aFamily($this->project));
        $bad = $this->member->pushSubscriptions()->create(['endpoint' => 'https://fcm.googleapis.com/bad', 'public_key' => 'x', 'auth_token' => 'y']);
        allowDevice($this, $other, 'https://fcm.googleapis.com/other');
        $sender = senderThrowingFor($bad->endpoint);

        app(ChatService::class)->send(
            app(ChatService::class)->start($this->project, $this->admin, [$this->member->id, $other->id]),
            $this->admin,
            'Hello',
        );

        expect(collect($sender->sent)->pluck('endpoint')->all())->toBe(['https://fcm.googleapis.com/other']);
    });

    it('is forgotten when the sender cannot encrypt for it, with no call to anywhere', function () {
        $device = $this->member->pushSubscriptions()->create(['endpoint' => 'https://fcm.googleapis.com/bad', 'public_key' => 'x', 'auth_token' => 'y']);

        expect((new WebPushSender())->send($device, ['title' => 't', 'message' => 'm', 'url' => '/', 'tag' => 't']))->toBeFalse();
    });
});

it('pushes a project\'s notification to its people, each in their language, but not to whoever caused it', function () {
    allowDevice($this, $this->member, 'https://fcm.googleapis.com/member');
    allowDevice($this, $this->admin, 'https://fcm.googleapis.com/admin');
    $outsider = aMemberOf(aFamily(aProject()));
    allowDevice($this, $outsider, 'https://fcm.googleapis.com/outsider');

    app(NotificationService::class)->send(
        NotificationTarget::PROJECT,
        NotificationType::LOTTERY_COMPLETED,
        'notifications.lottery_completed_title',
        'notifications.lottery_completed_message',
        url: route('lottery.show', $this->project),
        trigger: $this->admin,
        targetId: $this->project->id,
    );

    expect($this->sender->sent)->toHaveCount(1)
        ->and($this->sender->sent[0])->toMatchArray([
            'endpoint' => 'https://fcm.googleapis.com/member',
            'title'    => __('notifications.lottery_completed_title', [], 'es_UY'),
            'url'      => route('lottery.show', $this->project, absolute: false),
        ]);
});

it('forgets a device its browser dropped', function () {
    allowDevice($this, $this->member, 'https://fcm.googleapis.com/dropped');
    $this->sender->gone = ['https://fcm.googleapis.com/dropped'];

    app(NotificationService::class)->send(NotificationTarget::PRIVATE, NotificationType::LOTTERY_FAILED, 'notifications.lottery_failed_title', 'notifications.lottery_failed_message', targetId: $this->member->id);

    expect(PushSubscription::count())->toBe(0);
});

it('pushes nothing where the deployment has no keys', function () {
    config(['services.push.public_key' => null]);
    allowDevice($this, $this->member);

    app(NotificationService::class)->send(NotificationTarget::PRIVATE, NotificationType::LOTTERY_FAILED, 'notifications.lottery_failed_title', 'notifications.lottery_failed_message', targetId: $this->member->id);

    expect($this->sender->sent)->toBe([])
        ->and($this->as($this->member)->get(route('profile.edit'))->inertiaProps('pushKey'))->toBeNull();
});
