<?php

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
        ->assertSee('New event: General assembly')
        ->click('Mark all as read')
        ->assertSee('Nothing unread')
        ->click('Show all')
        ->assertSee('New event: General assembly');
});
