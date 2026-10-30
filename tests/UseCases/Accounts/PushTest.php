<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\PushSubscription;
use App\Services\NotificationService;
use App\Services\Push\PushSender;

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

function allowDevice($test, $user, string $endpoint = 'https://push.example.com/device-1')
{
    return $test->as($user)->post(route('push.subscribe'), [
        'endpoint' => $endpoint,
        'keys'     => ['p256dh' => 'public-key-of-the-browser', 'auth' => 'auth-secret'],
    ]);
}

it('keeps a device someone allows, and forgets it when they turn it off', function () {
    allowDevice($this, $this->member)->assertSessionHasNoErrors();
    expect($this->member->pushSubscriptions()->sole()->endpoint)->toBe('https://push.example.com/device-1');

    $this->as($this->member)->delete(route('push.unsubscribe'), ['endpoint' => 'https://push.example.com/device-1']);
    expect(PushSubscription::count())->toBe(0);
});

it('gives a device to whoever allows it last (someone else signed in there)', function () {
    allowDevice($this, $this->member);
    allowDevice($this, $this->admin);

    expect(PushSubscription::sole()->user_id)->toBe($this->admin->id);
});

it('never lets anyone turn off someone else\'s device', function () {
    allowDevice($this, $this->member);
    $this->as($this->admin)->delete(route('push.unsubscribe'), ['endpoint' => 'https://push.example.com/device-1']);

    expect(PushSubscription::count())->toBe(1);
});

it('takes only secure push addresses', function () {
    allowDevice($this, $this->member, 'http://push.example.com/plain')->assertSessionHasErrors('endpoint');
});

it('pushes a project\'s notification to its people, each in their language, but not to whoever caused it', function () {
    allowDevice($this, $this->member, 'https://push.example.com/member');
    allowDevice($this, $this->admin, 'https://push.example.com/admin');
    $outsider = aMemberOf(aFamily(aProject()));
    allowDevice($this, $outsider, 'https://push.example.com/outsider');

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
            'endpoint' => 'https://push.example.com/member',
            'title'    => __('notifications.lottery_completed_title', [], 'es_UY'),
            'url'      => route('lottery.show', $this->project, absolute: false),
        ]);
});

it('forgets a device its browser dropped', function () {
    allowDevice($this, $this->member, 'https://push.example.com/dropped');
    $this->sender->gone = ['https://push.example.com/dropped'];

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
