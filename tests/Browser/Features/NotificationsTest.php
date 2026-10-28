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
        route('events.index', $this->project),
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
