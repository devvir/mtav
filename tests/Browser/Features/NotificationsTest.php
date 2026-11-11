<?php

use App\Models\PushSubscription;
use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Services\NotificationService;

/**
 * Notifications: the bell shows the latest ones where one is; the whole list has its page.
 */
beforeEach(function () {
    $this->project = aProject();
    $this->member = aMemberOf(aFamily($this->project));

    app(NotificationService::class)->send(
        NotificationTarget::PROJECT,
        NotificationType::RESOURCE_CREATED,
        'New event: General assembly',
        'On Friday at 10, in the communal hall',
        url: route('events.index', $this->project),
        targetId: $this->project->id,
    );

    $this->as($this->member, $this->project);
});

it('shows the latest from the bell, staying on the page, and all of them a click away', function () {
    visit(route('projects.show', $this->project))
        ->click('[aria-label="Notifications, 1 unread"]')
        ->assertSee('New event: General assembly')
        ->assertPathIs("/projects/{$this->project->id}")
        ->click('See all notifications')
        ->assertPathIs('/notifications')
        ->assertSee('On Friday at 10, in the communal hall');
});

it('opens what a notification is about, and marks it read', function () {
    visit(route('projects.show', $this->project))
        ->click('[aria-label="Notifications, 1 unread"]')
        ->click('.bell-list >> text=New event: General assembly')
        ->assertPathIs("/projects/{$this->project->id}/events")
        ->assertPresent('[aria-label="Notifications"]');
});

it('marks every notification read from the bell', function () {
    app(NotificationService::class)->send(
        NotificationTarget::PROJECT,
        NotificationType::RESOURCE_CREATED,
        'New file: Minutes',
        'The minutes of the last assembly',
        url: route('media.index', $this->project),
        targetId: $this->project->id,
    );

    visit(route('projects.show', $this->project))
        ->click('[aria-label="Notifications, 2 unread"]')
        ->click('Mark all as read')
        ->assertPresent('[aria-label="Notifications"]')
        ->assertDontSee('Mark all as read');
});

it('shows only the unread ones on demand, and marks them all read from the list', function () {
    visit(route('notifications.index'))
        ->click('Only unread')
        ->assertSee('Show all')
        ->assertSee('New event: General assembly')
        ->click('Mark all as read')
        ->assertSee('Nothing unread')
        ->click('Show all')
        ->assertSee('New event: General assembly');
});

it('names a row by its title and whether it was read, with the rest as its description', function () {
    $page = visit(route('notifications.index'));

    // Text in a button stays what a button may hold
    $page->assertScript("document.querySelectorAll('.notification p, .notification div').length", 0);

    $name = "() => { const row = document.querySelector('.notification'); return row.getAttribute('aria-labelledby').split(' ').map((id) => document.getElementById(id).textContent).join(' · '); }";
    $about = "() => document.getElementById(document.querySelector('.notification').getAttribute('aria-describedby')).textContent";

    expect($page->script($name))->toBe('New event: General assembly · Unread')
        ->and($page->script($about))->toContain('On Friday at 10, in the communal hall');
});

it('says which are unread to whoever cannot see the mark', function () {
    visit(route('projects.show', $this->project))
        ->click('[aria-label="Notifications, 1 unread"]')
        ->assertSeeIn('.bell-list .notification[data-unread]', 'Unread');
});

it('keeps this device for pushes, refuses an address no push service has, and forgets the device', function () {
    $page = visit(route('notifications.index'));
    $send = fn (string $method, string $endpoint) => $page->script(<<<JS
        async () => {
            // A real key, as a browser's push subscription has: a point of the P-256 curve
            const pair = await crypto.subtle.generateKey({ name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveBits']);
            const encode = (bytes) => btoa(String.fromCharCode(...new Uint8Array(bytes))).replace(/\\+/g, '-').replace(/\\//g, '_').replace(/=+$/, '');
            const keys = { p256dh: encode(await crypto.subtle.exportKey('raw', pair.publicKey)), auth: encode(crypto.getRandomValues(new Uint8Array(16))) };
            const token = decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)[1]);
            const response = await fetch('/push-subscriptions', {
                method: '{$method}',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': token },
                body: JSON.stringify({ endpoint: '{$endpoint}', keys }),
                // Not followed: the app's own calls go back to the page they came from, as Inertia does
                redirect: 'manual',
            });
            return response.type === 'opaqueredirect' ? 302 : response.status;
        }
        JS);

    $device = 'https://fcm.googleapis.com/fcm/send/this-device';
    expect($send('POST', $device))->toBe(302)
        ->and(PushSubscription::where('user_id', $this->member->id)->pluck('endpoint')->all())->toBe([$device]);

    expect($send('POST', 'https://push.example.com/elsewhere'))->toBe(422)
        ->and(PushSubscription::count())->toBe(1);

    expect($send('DELETE', $device))->toBe(302)
        ->and(PushSubscription::count())->toBe(0);
});
