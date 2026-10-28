<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Services\NotificationService;

/**
 * Opening a notification marks it read and leads to what it is about; one about nothing in
 * particular is marked read where it is.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->member = aMemberOf(aFamily($this->project));
});

function notify($test, ?string $url): Notification
{
    return app(NotificationService::class)->send(
        NotificationTarget::PROJECT,
        NotificationType::RESOURCE_CREATED,
        'New event: General assembly',
        'On Friday at 10, in the communal hall',
        $url,
        targetId: $test->project->id,
    );
}

it('marks a notification read and leads to what it is about', function () {
    $notification = notify($this, route('events.index', $this->project));

    $this->as($this->member, $this->project)
        ->from(route('projects.show', $this->project))
        ->post(route('notifications.read', $notification))
        ->assertRedirect(route('events.index', $this->project, absolute: false));

    expect(Notification::for($this->member)->unreadBy($this->member)->count())->toBe(0);
});

it('marks a notification about nothing in particular read, staying on the page', function () {
    $notification = notify($this, null);

    $this->as($this->member, $this->project)
        ->from(route('projects.show', $this->project))
        ->post(route('notifications.read', $notification))
        ->assertRedirect(route('projects.show', $this->project));

    expect(Notification::for($this->member)->unreadBy($this->member)->count())->toBe(0);
});

it('keeps notifications of other projects out of reach', function () {
    $notification = notify($this, route('events.index', $this->project));

    $outsider = aMemberOf(aFamily(aProject()));

    expect($this->as($outsider)->post(route('notifications.read', $notification)))->toBeDenied();
    expect($notification->readers()->count())->toBe(0);
});
