<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\PushSubscription;
use App\Services\ChatService;
use App\Services\NotificationService;
use App\Services\Push\PushSender;
use App\Services\Push\PushService;
use App\Services\Push\WebPushSender;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Password;

/**
 * Notifications on people's devices, even with the app closed: each person allows the
 * devices they want; each notification reaches everyone it is for (but whoever caused
 * it), in their own language; a device the browser dropped is forgotten.
 */

/** Delivers nothing: keeps what it was handed on each flush, and says which subscriptions are gone */
class FakePushSender implements PushSender
{
    public array $sent = [];

    public array $gone = [];

    private array $held = [];

    public function queue(PushSubscription $to, array $payload): void
    {
        $this->held[] = [$to, $payload];
    }

    public function flush(): array
    {
        [$held, $this->held] = [$this->held, []];
        foreach ($held as [$to, $payload]) {
            $this->sent[] = ['endpoint' => $to->endpoint, 'user' => $to->user_id, ...$payload];
        }

        return collect($held)->map(fn (array $one) => $one[0])->filter(fn (PushSubscription $to) => in_array($to->endpoint, $this->gone, true))->values()->all();
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

/** The deployment's own VAPID keys, real ones: the library signs with them */
function realPushKeys(): void
{
    $keys = Minishlink\WebPush\VAPID::createVapidKeys();
    config(['services.push.subject' => 'mailto:test@example.com', 'services.push.public_key' => $keys['publicKey'], 'services.push.private_key' => $keys['privateKey']]);
}

/** A device's keys as a browser makes them: a P-256 public key and a 16-byte secret */
function realDeviceKeys(): array
{
    $encode = fn (string $bytes) => rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    $ec = openssl_pkey_get_details(openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]))['ec'];

    return ['public_key' => $encode("\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT)), 'auth_token' => $encode(random_bytes(16))];
}

function devicePointNotOnTheCurve(): array
{
    $keys = browserKeys();

    return ['public_key' => $keys['p256dh'], 'auth_token' => $keys['auth']];
}

/**
 * The browsers' push services, answering each address with a status (201, by default), one by
 * one or all at once, and keeping where they were called
 */
function pushServiceAnswering(array $statuses): object
{
    return new class ($statuses) implements Psr\Http\Client\ClientInterface, Http\Client\HttpAsyncClient {
        public array $endpoints = [];

        public function __construct(private array $statuses)
        {
        }

        public function sendRequest(Psr\Http\Message\RequestInterface $request): Psr\Http\Message\ResponseInterface
        {
            $this->endpoints[] = $endpoint = (string) $request->getUri();

            return new GuzzleHttp\Psr7\Response($this->statuses[$endpoint] ?? 201);
        }

        public function sendAsyncRequest(Psr\Http\Message\RequestInterface $request): Http\Promise\Promise
        {
            return new Http\Promise\FulfilledPromise($this->sendRequest($request));
        }
    };
}

function webPushThrough(object $pushServices): WebPushSender
{
    return new WebPushSender($pushServices, $pushServices);
}

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
        $chat->post($chat->start($this->project, $this->writer, $people, $title), $this->writer, 'Hello');

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
        $chat->post($group, $this->writer, 'Hello');

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

            public function queue(PushSubscription $to, array $payload): void
            {
                if ($to->endpoint === $this->bad) {
                    throw new ErrorException('Invalid client public key length.');
                }

                parent::queue($to, $payload);
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

        app(ChatService::class)->post(
            app(ChatService::class)->start($this->project, $this->admin, [$this->member->id, $other->id]),
            $this->admin,
            'Hello',
        );

        expect(collect($sender->sent)->pluck('endpoint')->all())->toBe(['https://fcm.googleapis.com/other']);
    });

    it('is forgotten when the sender cannot encrypt for it, with no call to anywhere', function () {
        $device = $this->member->pushSubscriptions()->create(['endpoint' => 'https://fcm.googleapis.com/bad', 'public_key' => 'x', 'auth_token' => 'y']);

        $calls = pushServiceAnswering([]);
        $sender = webPushThrough($calls);
        $sender->queue($device, ['title' => 't', 'message' => 'm', 'url' => '/', 'tag' => 't']);

        expect(collect($sender->flush())->pluck('id')->all())->toBe([$device->id])
            ->and($sender->flush())->toBe([])
            ->and($calls->endpoints)->toBe([]);
    });

    it('sends everyone else\'s at once when one device\'s keys cannot be encrypted with, and tells the error log', function () {
        Exceptions::fake();
        realPushKeys();
        $calls = pushServiceAnswering(['https://fcm.googleapis.com/gone' => 410]);
        // Of a browser's shape, but no point on the curve: only encrypting finds out
        $bad = $this->member->pushSubscriptions()->create(['endpoint' => 'https://fcm.googleapis.com/bad', ...devicePointNotOnTheCurve()]);
        $good = $this->member->pushSubscriptions()->create(['endpoint' => 'https://fcm.googleapis.com/good', ...realDeviceKeys()]);
        $gone = $this->admin->pushSubscriptions()->create(['endpoint' => 'https://fcm.googleapis.com/gone', ...realDeviceKeys()]);

        $sender = webPushThrough($calls);
        foreach ([$bad, $good, $gone] as $device) {
            $sender->queue($device, ['title' => 't', 'message' => 'm', 'url' => '/', 'tag' => 't']);
        }
        $dropped = $sender->flush();

        expect($calls->endpoints)->toEqualCanonicalizing(['https://fcm.googleapis.com/good', 'https://fcm.googleapis.com/gone'])
            ->and(collect($dropped)->pluck('id')->all())->toBe([$gone->id]);
        Exceptions::assertReported(RuntimeException::class);
        Exceptions::assertReportedCount(1);
    });

    it('sends what it holds in one go, each to its own push service', function () {
        realPushKeys();
        $calls = pushServiceAnswering([]);
        $sender = webPushThrough($calls);
        foreach (['https://fcm.googleapis.com/one', 'https://updates.push.services.mozilla.com/two'] as $endpoint) {
            $sender->queue($this->member->pushSubscriptions()->create(['endpoint' => $endpoint, ...realDeviceKeys()]), ['title' => 't', 'message' => 'm', 'url' => '/', 'tag' => 't']);
        }

        expect($calls->endpoints)->toBe([])
            ->and($sender->flush())->toBe([])
            ->and($calls->endpoints)->toEqualCanonicalizing(['https://fcm.googleapis.com/one', 'https://updates.push.services.mozilla.com/two']);
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

it('turns off, on signing out, the device signed out of, and only that one', function () {
    allowDevice($this, $this->member);
    allowDevice($this, $this->member, 'https://fcm.googleapis.com/device-2');
    allowDevice($this, $this->admin, 'https://fcm.googleapis.com/device-3');

    $this->as($this->member)->post(route('logout'), ['endpoint' => 'https://fcm.googleapis.com/device-1'])
        ->assertRedirect(route('home'));

    expect(PushSubscription::pluck('endpoint')->all())
        ->toEqualCanonicalizing(['https://fcm.googleapis.com/device-2', 'https://fcm.googleapis.com/device-3']);
});

it('does not turn off, on signing out, another person\'s device, whatever the browser says', function () {
    allowDevice($this, $this->admin);

    $this->as($this->member)->post(route('logout'), ['endpoint' => 'https://fcm.googleapis.com/device-1']);

    expect($this->admin->pushSubscriptions()->count())->toBe(1);
});

it('signs out a browser that has no device to name', function () {
    allowDevice($this, $this->member);

    $this->as($this->member)->post(route('logout'), ['endpoint' => null])->assertRedirect(route('home'));

    expect($this->member->pushSubscriptions()->count())->toBe(1);
    $this->assertGuest();
});

it('turns off all of someone\'s devices when they change their password, and nobody else\'s', function () {
    allowDevice($this, $this->member);
    allowDevice($this, $this->member, 'https://fcm.googleapis.com/device-2');
    allowDevice($this, $this->admin, 'https://fcm.googleapis.com/device-3');

    $this->as($this->member)->put(route('password.update'), [
        'current_password'      => 'password',
        'password'              => 'a-New-password-9',
        'password_confirmation' => 'a-New-password-9',
    ])->assertSessionHasNoErrors();

    expect($this->member->pushSubscriptions()->count())->toBe(0)
        ->and($this->admin->pushSubscriptions()->count())->toBe(1);
});

it('keeps someone\'s devices when the new password is refused', function () {
    allowDevice($this, $this->member);

    $this->as($this->member)->put(route('password.update'), [
        'current_password'      => 'not-the-password',
        'password'              => 'a-New-password-9',
        'password_confirmation' => 'a-New-password-9',
    ])->assertSessionHasErrors('current_password');

    expect($this->member->pushSubscriptions()->count())->toBe(1);
});

it('turns off all of someone\'s devices when they reset their password, and nobody else\'s', function () {
    allowDevice($this, $this->member);
    allowDevice($this, $this->admin, 'https://fcm.googleapis.com/device-3');
    auth()->logout();

    $this->post(route('password.store'), [
        'token'                 => Password::createToken($this->member),
        'email'                 => $this->member->email,
        'password'              => 'a-New-password-9',
        'password_confirmation' => 'a-New-password-9',
    ])->assertSessionHasNoErrors();

    expect($this->member->pushSubscriptions()->count())->toBe(0)
        ->and($this->admin->pushSubscriptions()->count())->toBe(1);
});

it('tells the profile which devices send to this person, and only those', function () {
    allowDevice($this, $this->member);
    allowDevice($this, $this->admin, 'https://fcm.googleapis.com/device-3');

    $this->as($this->member)->get(route('profile.edit'))->assertInertia(
        fn ($page) => $page->where('pushDevices', ['https://fcm.googleapis.com/device-1']),
    );
});

describe('Whom a notification reaches on a device', function () {
    beforeEach(function () {
        allowDevice($this, $this->member, 'https://fcm.googleapis.com/member');
        $this->notice = fn () => Notification::create([
            'type' => NotificationType::FORUM_TOPIC, 'target' => NotificationTarget::PROJECT, 'target_id' => $this->project->id,
            'data' => ['title' => 'A title', 'message' => 'A message'],
        ]);
    });

    it('pushes nothing to someone about what they did themselves, though the bell has it', function () {
        app(NotificationService::class)->send(NotificationTarget::PRIVATE, NotificationType::LOTTERY_FAILED, 'notifications.lottery_failed_title', 'notifications.lottery_failed_message', trigger: $this->member, targetId: $this->member->id);

        expect($this->sender->sent)->toBe([]);
    });

    it('pushes a project\'s notification only to those it is for: not to who joined after it', function () {
        $notice = ($this->notice)();
        $this->travel(1)->minutes();
        $newcomer = aMemberOf(aFamily($this->project));
        allowDevice($this, $newcomer, 'https://fcm.googleapis.com/newcomer');

        app(PushService::class)->deliver($notice);

        expect(collect($this->sender->sent)->pluck('endpoint')->all())->toBe(['https://fcm.googleapis.com/member']);
    });

    it('pushes nothing of a project archived meanwhile', function () {
        $notice = ($this->notice)();
        $this->project->delete();

        app(PushService::class)->deliver($notice);

        expect($this->sender->sent)->toBe([]);
    });
});
