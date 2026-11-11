<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Services\ChatService;
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

it('shows a message in the conversation open, its new words when edited, and takes it away when deleted', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->admin, [$this->member->id]);
    $page = visit(route('chat.show', [$this->project, $conversation]));
    listening($page, "users.{$this->member->id}");

    $message = $chat->send($conversation, $this->admin, 'Are you coming on Saturday?');
    $page->assertSeeIn('.chat-messages', 'Are you coming on Saturday?');

    $chat->edit($message, 'Are you coming on Sunday?');
    $page->assertSeeIn('.chat-messages', 'Are you coming on Sunday?')
        ->assertDontSeeIn('.chat-messages', 'Saturday');

    $chat->remove($message);
    $page->assertDontSeeIn('.chat-messages', 'Are you coming on Sunday?');
});

it('counts a conversation with something new once, and says who wrote, away from the chat', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->admin, [$this->member->id]);
    $page = visit(route('projects.show', $this->project));
    listening($page, "users.{$this->member->id}");

    $chat->send($conversation, $this->admin, 'One');
    $page->assertSee("New message from {$this->admin->firstname}");

    $chat->send($conversation, $this->admin, 'Two');
    $page->assertPresent('.chat-launcher');

    expect($conversation->participants()->whereKey($this->member->id)->first()->pivot->read_up_to)->toBeNull();
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
        url: route('events.index', $this->project),
        trigger: $this->admin,
        targetId: $this->project->id,
    );

    $page->assertSee('New event: General assembly')
        ->assertPresent('[aria-label="Notifications, 1 unread"]');
});

it('does not ask whether to leave the plan when something arrives while it is unsaved', function () {
    $this->as($this->admin, $this->project);

    $page = visit(route('units.index', $this->project))->click('Edit the plan')->assertPresent('[aria-label="Plan editor"]');
    listening($page, "projects.{$this->project->id}");
    $page->script('() => {
        window.asked = 0;
        window.confirm = () => { window.asked += 1; return false; };
        window.fetch = () => Promise.reject(new TypeError("Failed to fetch"));
    }');
    $page->click('.plan-palette >> ' . button('Green area'));

    auth()->setUser($this->member);
    app(NotificationService::class)->send(
        NotificationTarget::PROJECT,
        NotificationType::RESOURCE_CREATED,
        'New event: General assembly',
        'On Friday at 10',
        url: route('events.index', $this->project),
        targetId: $this->project->id,
    );
    auth()->setUser($this->admin);

    $page->assertPresent('[aria-label="Notifications, 1 unread"]');
    expect($page->script('() => window.asked'))->toBe(0);
    $page->assertPresent('[aria-label="Plan editor"]');
});

it('stays live on a channel after a page that listened there goes', function () {
    $page = visit(route('lottery.show', $this->project))->assertPathIs("/projects/{$this->project->id}/lottery");
    listening($page, "projects.{$this->project->id}");

    // The page's own listeners stop and start; the shell's go on
    $page->click('.shell-sidebar >> a:has-text("Home")')->assertPathIs("/projects/{$this->project->id}");
    listening($page, "projects.{$this->project->id}");

    byTheAdmin($this, fn () => app(NotificationService::class)->send(
        NotificationTarget::PROJECT,
        NotificationType::RESOURCE_CREATED,
        'New event: General assembly',
        'On Friday at 10',
        url: route('events.index', $this->project),
        targetId: $this->project->id,
    ));

    $page->assertPresent('[aria-label="Notifications, 1 unread"]');
});

it('shows a person their own change made elsewhere, on their phone or in another tab', function () {
    $page = visit(route('events.index', $this->project))
        ->assertDontSee('Assembly on the roof');

    listening($page, "projects.{$this->project->id}");

    // The member themselves, from another device
    anEvent($this->project, ['title' => 'Assembly on the roof', 'creator_id' => $this->member->id]);

    $page->assertSee('Assembly on the roof');
});
