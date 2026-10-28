<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Broadcast;

/**
 * Live updates: what someone else does shows up on open pages, without reloading. The
 * browser suite runs a websocket server (Reverb) beside the app; broadcasting, off in
 * the other tests, is on here.
 */

beforeEach(function () {
    // Channels are registered on the broadcaster in use at boot: the null one, in tests
    config()->set('broadcasting.default', 'reverb');
    Broadcast::purge();
    require base_path('routes/channels.php');

    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project));

    $this->as($this->member, $this->project);
});

/** The page listens on the project's and the person's channels */
function listening(object $page, string $channel): object
{
    return $page->assertScript(
        "window.Echo?.connector?.pusher?.channel('private-{$channel}')?.subscribed === true",
        true,
    );
}

/** Something the admin does while the member's page is open */
function byTheAdmin($test, callable $change): void
{
    auth()->setUser($test->admin);
    $change();
    auth()->setUser($test->member);
}

it('shows an event someone else adds, without reloading', function () {
    $page = visit(route('events.index', $this->project))
        ->assertDontSee('Night walk around the site');

    listening($page, "projects.{$this->project->id}");

    byTheAdmin($this, fn () => anEvent($this->project, [
        'title'      => 'Night walk around the site',
        'creator_id' => $this->admin->id,
    ]));

    $page->assertSee('Night walk around the site');
});

it('rings the bell the moment a notification is sent', function () {
    $page = visit(route('projects.show', $this->project))
        ->assertPresent('[aria-label="Notifications"]');

    listening($page, "projects.{$this->project->id}");

    app(NotificationService::class)->send(
        NotificationTarget::PROJECT,
        NotificationType::RESOURCE_CREATED,
        'New event: General assembly',
        'On Friday at 10, in the communal hall',
        route('events.index', $this->project),
        $this->admin,
        $this->project->id,
    );

    $page->assertSee('New event: General assembly')
        ->assertPresent('[aria-label="Notifications, 1 unread"]');
});
