<?php

use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatService;

/**
 * The chat: starting a conversation, writing in it, and the other person finding it
 * waiting for them, marked unread until they open it.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->ana = aMemberOf(aFamily($this->project), ['firstname' => 'Ana', 'lastname' => 'Pérez']);
    $this->beto = aMemberOf(aFamily($this->project), ['firstname' => 'Beto', 'lastname' => 'Silva']);
});

it('says who wrote each message and when, to whoever cannot see the bubbles, and announces new ones', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->post($conversation, $this->ana, 'Hola Beto');
    $chat->post($conversation, $this->beto, 'Hola Ana');
    $this->as($this->beto);

    visit(route('chat.show', [$this->project, $conversation]))
        ->assertPresent('.chat-messages[role="log"][aria-live="polite"][aria-relevant="additions"]')
        ->assertPresent('.chat-message time[datetime]')
        ->assertSeeIn('.chat-message:has-text("Hola Beto") .chat-bubble', 'Ana Pérez:')
        ->assertSeeIn('.chat-message:has-text("Hola Ana") .chat-bubble', 'You:')
        ->assertNoSmoke();
});

it('names a group with no name by its people, offers an empty name to rename it, and leaves it nameless on adding people', function () {
    $carla = aMemberOf(aFamily($this->project), ['firstname' => 'Carla', 'lastname' => 'Díaz']);
    $dora = aMemberOf(aFamily($this->project), ['firstname' => 'Dora', 'lastname' => 'Luz']);
    $group = app(ChatService::class)->start($this->project, $this->ana, [$this->beto->id, $carla->id]);
    $this->as($this->ana);

    $page = visit(route('chat.show', [$this->project, $group]))
        ->click('[aria-label="Conversation options"]')
        ->click('Add people')
        ->click('[role="dialog"] input[role="combobox"]')
        ->click('[role="option"]:has-text("Dora Luz")')
        ->click(inDialog('Add'))
        ->assertMissing('[role="dialog"]');

    expect($group->fresh()->title)->toBeNull()
        ->and($group->participants()->whereKey($dora->id)->exists())->toBeTrue();

    $page->click('[aria-label="Conversation options"]')
        ->click('Rename the group')
        ->assertValue('[role="dialog"] input', '');
});

it('starts a conversation with someone, and they find it waiting, unread until opened', function () {
    $this->as($this->ana);

    visit(route('chat.index', $this->project))
        ->click('New conversation')
        ->click('[role="dialog"] input[role="combobox"]')
        ->click('[role="option"]:has-text("Beto Silva")')
        ->click(inDialog('Start the conversation'))
        ->assertSee('Say hello: the first message starts the conversation.')
        ->fill('[aria-label="Message"]', 'Hola Beto, ¿vienes el sábado?')
        ->keys('[aria-label="Message"]', 'Enter')
        ->assertSeeIn('.chat-messages', 'Hola Beto, ¿vienes el sábado?')
        ->assertNoSmoke();

    $this->as($this->beto);
    visit(route('projects.show', $this->project))
        ->click('.chat-launcher')
        ->assertPresent('.chat-list-item[data-unread]')
        ->click('.chat-list-item:has-text("Ana Pérez")')
        ->assertSeeIn('.chat-messages', 'Hola Beto, ¿vienes el sábado?')
        ->assertMissing('.chat-list-item[data-unread]')
        ->assertPathIs('/projects/' . $this->project->id);

    expect(Conversation::where('is_community', false)->where('is_assistant', false)->sole()->messages()->count())->toBe(1);
});

it('starts a named group, takes back a message, pins the group and leaves it', function () {
    $carla = aMemberOf(aFamily($this->project), ['firstname' => 'Carla', 'lastname' => 'Díaz']);
    $this->as($this->ana);

    $page = visit(route('chat.index', $this->project))
        ->click('New conversation')
        ->click('[role="dialog"] input[role="combobox"]')
        ->click('[role="option"]:has-text("Beto Silva")')
        ->click('[role="dialog"] input[role="combobox"]')
        ->click('[role="option"]:has-text("Carla Díaz")')
        ->fill('[role="dialog"] input[maxlength="80"]', 'Building committee')
        ->click(inDialog('Start the group'))
        ->assertSeeIn('.chat-thread-header', 'Building committee');

    $group = Conversation::where('is_community', false)->where('is_assistant', false)->sole();
    expect($group)->is_group->toBeTrue()->title->toBe('Building committee');

    // A message sent too soon, taken back
    $page->fill('[aria-label="Message"]', 'Meeting on Friday?')
        ->keys('[aria-label="Message"]', 'Enter')
        ->assertSeeIn('.chat-messages', 'Meeting on Friday?')
        ->click('.chat-message[data-mine] [aria-label="Delete this message"]')
        ->click(inDialog('Delete'))
        ->assertDontSeeIn('.chat-messages', 'Meeting on Friday?');
    expect($group->messages()->count())->toBe(0);

    // Pinned first in the list of groups
    $page->click('.chat-kinds label:has-text("Groups")')
        ->click('.chat-list-row:has-text("Building committee") [aria-label="Pin"]')
        ->assertPresent('.chat-list-row:has-text("Building committee") [aria-label="Unpin"]');

    // Left: it no longer shows, and the others stay in it
    $page->click('.chat-list-item:has-text("Building committee")')
        ->click('[aria-label="Conversation options"]')
        ->click('[role="menuitem"]:has-text("Leave the group")')
        ->click(inDialog('Leave'))
        ->assertMissing('.chat-list-item:has-text("Building committee")')
        ->assertNoSmoke();
    expect($group->fresh()->includes($this->ana))->toBeFalse()
        ->and($group->fresh()->includes($carla))->toBeTrue();
});

it('opens from its bubble over the page, writes, folds back, and stays open between pages', function () {
    $this->as($this->ana);
    $path = '/projects/' . $this->project->id;

    $page = visit(route('projects.show', $this->project))
        ->assertMissing('.chat-window')
        ->click('.chat-launcher')
        ->assertPresent('.chat-window')
        ->click('.chat-window button:has-text("New conversation")')
        ->click('[role="dialog"] input[role="combobox"]')
        ->click('[role="option"]:has-text("Beto Silva")')
        ->click(inDialog('Start the conversation'))
        ->assertSeeIn('.chat-window', 'Say hello')
        ->fill('.chat-window [aria-label="Message"]', 'Hola desde la burbuja')
        ->keys('.chat-window [aria-label="Message"]', 'Enter')
        ->assertSeeIn('.chat-window .chat-messages', 'Hola desde la burbuja')
        ->assertPathIs($path)
        ->click('.chat-window [aria-label="Minimize"]')
        ->assertMissing('.chat-window')
        ->assertPathIs($path)
        ->click('.chat-launcher')
        ->assertSeeIn('.chat-window .chat-messages', 'Hola desde la burbuja')
        ->assertNoSmoke();

    // Moving on, it stays as it was
    $page->click('.shell-nav a:has-text("Lottery")')
        ->assertPathIs($path . '/lottery')
        ->assertSeeIn('.chat-window .chat-messages', 'Hola desde la burbuja')
        ->keys('.chat-window [aria-label="Message"]', 'Escape')
        ->assertMissing('.chat-window');
});

it('has the Assistant at hand, pinned, offering what to ask, and says when it is not available', function () {
    $this->as($this->ana);

    visit(route('projects.show', $this->project))
        ->click('.chat-launcher')
        ->assertPresent('.chat-kinds + .chat-list-row .chat-list-item:has-text("Assistant")')
        ->assertPresent('.chat-kinds + .chat-list-row .chat-pin[data-pinned]')
        // Its greeting is there before anything is written: the list says so, not "no messages"
        ->assertSeeIn('.chat-kinds + .chat-list-row', 'I am the Assistant of this app')
        ->assertDontSeeIn('.chat-kinds + .chat-list-row', 'No messages yet')
        ->click('.chat-list-item:has-text("Assistant")')
        ->assertSeeIn('.chat-window .chat-thread', 'I am the Assistant of this app')
        ->assertPresent('.chat-window button:has-text("Show me around")')
        ->click('.chat-window button:has-text("How does the lottery work?")')
        ->assertSeeIn('.chat-messages', 'How does the lottery work?')
        ->assertSeeIn('.chat-messages', 'Each family ranks the units')
        // Its greeting stays where the conversation starts; what to ask was for before it began
        ->assertSeeIn('.chat-window .chat-thread', 'I am the Assistant of this app')
        ->assertMissing('.chat-window button:has-text("How does the lottery work?")')
        ->fill('.chat-composer textarea, .chat-composer input', 'Where do I see the plan?')
        ->keys('.chat-composer textarea, .chat-composer input', 'Enter')
        ->assertSeeIn('.chat-messages', 'The Assistant is not available here yet')
        ->assertNoSmoke();
});

it('leads somewhere from the Assistant\'s buttons, and sends its replies with a tap', function () {
    $conversation = Conversation::where('pair', "assistant:{$this->ana->id}")->sole();
    app(App\Services\Assistant\Assistant::class)->say($conversation, 'Here it is', [
        ['type' => 'link', 'label' => 'Go to the lottery', 'href' => route('lottery.show', $this->project, absolute: false)],
        ['type' => 'reply', 'label' => 'Tell me more about it'],
    ]);
    $this->as($this->ana);

    visit(route('projects.show', $this->project))
        ->click('.chat-launcher')
        ->click('.chat-list-item:has-text("Assistant")')
        ->assertSeeIn('.chat-messages', 'Here it is')
        ->click('.chat-actions button:has-text("Tell me more about it")')
        ->assertPresent('.chat-message[data-mine]')
        ->click('.chat-actions button:has-text("Go to the lottery")')
        ->assertPathIs('/projects/' . $this->project->id . '/lottery')
        // The window folds away, or there would be nothing to see of where it led
        ->assertMissing('.chat-window')
        ->assertNoSmoke();
});

it('sends a report it drafted as shown, once, and opens a conversation with an admin', function () {
    $admin = anAdminOf($this->project);
    $conversation = Conversation::where('pair', "assistant:{$this->ana->id}")->sole();
    app(App\Services\Assistant\Assistant::class)->say($conversation, 'I can tell the team', [
        ['type' => 'report', 'label' => 'Send the report', 'message' => 'The plan does not open on my phone'],
        ['type' => 'message', 'label' => 'Write to an admin'],
    ]);
    $this->as($this->ana);

    visit(route('projects.show', $this->project))
        ->click('.chat-launcher')
        ->click('.chat-list-item:has-text("Assistant")')
        ->assertSeeIn('.chat-report', 'The plan does not open on my phone')
        ->click('.chat-actions button:has-text("Send the report")')
        ->assertSeeIn('.chat-actions', 'Sent')
        ->click('.chat-actions button:has-text("Write to an admin")')
        ->assertSeeIn('.chat-window', 'Say hello')
        ->assertNoSmoke();

    expect(App\Models\Report::sole()->body)->toBe('The plan does not open on my phone')
        ->and(Conversation::where('pair', Conversation::pairOf($this->ana->id, $admin->id))->exists())->toBeTrue();
});

it('gets over a message that could not leave: the button stops waiting, it says so, and the next one goes', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->post($conversation, $this->beto, 'Hola Ana');
    $this->as($this->ana);

    $page = visit(route('chat.show', [$this->project, $conversation]))->assertSee('Hola Ana');
    $page->script('() => {
        window.realFetch = window.fetch;
        window.fetch = () => Promise.reject(new TypeError("Failed to fetch"));
    }');

    $page->fill('[aria-label="Message"]', 'Sin conexión')
        ->keys('[aria-label="Message"]', 'Enter')
        ->assertSee("That didn't work. Try again.")
        ->assertValue('[aria-label="Message"]', 'Sin conexión')
        ->assertMissing('.chat-composer [data-loading]');

    $page->script('() => { window.fetch = window.realFetch; }');

    $page->keys('[aria-label="Message"]', 'Enter')
        ->assertSeeIn('.chat-messages', 'Sin conexión')
        ->assertValue('[aria-label="Message"]', '');
});

it('takes the focus into the window as it opens, and back to the bubble as it folds', function () {
    $this->as($this->ana);
    $inWindow = '() => document.activeElement.closest(".chat-window") !== null';
    $onBubble = '() => document.activeElement.classList.contains("chat-launcher")';

    $page = visit(route('projects.show', $this->project))->click('.chat-launcher')->assertPresent('.chat-window');
    expect($page->script($inWindow))->toBeTrue();

    $page->click('.chat-window [aria-label="Minimize"]')->assertMissing('.chat-window')->assertScript($onBubble, true);

    // By the keyboard too
    $page->script('() => document.querySelector(".chat-launcher").click()');
    $page->assertPresent('.chat-window')->keys('.chat-window', 'Escape')->assertMissing('.chat-window')->assertScript($onBubble, true);
});

it('says which conversations are unread to whoever cannot see the mark', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->post($conversation, $this->ana, 'Hola Beto');
    $this->as($this->beto);

    visit(route('chat.index', $this->project))
        ->assertPresent('.chat-list-item .chat-unread')
        ->assertSeeIn('.chat-list-item', 'Unread');
});

it('writes each message\'s time in the app\'s language, whatever the browser\'s', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->post($conversation, $this->ana, 'Hola Beto');
    $this->beto->update(['locale' => 'es_UY']);
    $this->as($this->beto);

    $page = visit(route('chat.show', [$this->project, $conversation]))->assertPresent('.chat-time');

    expect($page->script('() => {
        const time = document.querySelector(".chat-time");
        return time.textContent === new Intl.DateTimeFormat("es-UY", { timeStyle: "short" }).format(new Date(time.dateTime));
    }'))->toBeTrue();
});

it('says there are no groups, not no chats, on the groups of someone who has none', function () {
    $this->project->conversations()->delete();
    app(ChatService::class)->start($this->project, $this->ana, [$this->beto->id]);
    $this->as($this->ana);

    $page = visit(route('chat.index', $this->project))
        ->click('.chat-kinds >> text=Groups')
        ->assertSee('No groups yet.');

    expect($page->script('() => document.querySelector(".chat-list").innerText.includes("No one-on-one chats yet.")'))->toBeFalse();
});

it('says nothing matches, not that there are no chats, when a search finds none', function () {
    $people = collect(range(1, 13))->map(fn ($n) => aMemberOf(aFamily($this->project), ['firstname' => "Persona{$n}"]));
    $chat = app(ChatService::class);
    $people->each(fn ($person) => $chat->start($this->project, $this->ana, [$person->id]));
    $this->as($this->ana);

    visit(route('chat.index', $this->project))
        ->fill('[aria-label="Search conversations"]', 'nadie')
        ->assertSee('Nothing matches')
        ->assertDontSee('No one-on-one chats yet.');
});

it('is at hand on one\'s own pages too, in the project last browsed', function () {
    $this->as($this->ana);
    visit(route('projects.show', $this->project))->assertPresent('.chat-launcher');

    visit(route('profile.edit'))
        ->click('.chat-launcher')
        ->assertPresent('.chat-window')
        ->assertPresent('.chat-window .chat-list-item:has-text("Assistant")')
        ->assertNoSmoke();
});

it('is the Assistant alone outside any project: its conversation, with no list to go back to', function () {
    $this->as(aUser(['firstname' => 'Luz']));

    visit(route('projects.index'))
        ->click('.chat-launcher')
        ->assertSeeIn('.chat-window .chat-thread', 'I am the Assistant of this app')
        ->assertMissing('.chat-window .chat-list')
        ->assertMissing('.chat-window .chat-back')
        ->click('.chat-window button:has-text("What can I do here?")')
        ->assertSeeIn('.chat-messages', __('chat.ready.outside'))
        ->click('.chat-actions button:has-text("' . __('chat.ready.outside_action') . '")')
        ->assertPathIs(route('directory.index', absolute: false))
        ->assertNoSmoke();

    // Its own page, where phones have it: the same, on its own; a question asked, taken back
    // with its answer, and what to ask offered again
    visit(route('account.chat.index'))
        ->assertSeeIn('.chat-messages', __('chat.ready.outside'))
        ->assertMissing('.chat-list')
        ->click('.chat-message[data-mine] [aria-label="Delete this message"]')
        ->click(inDialog('Delete'))
        ->assertMissing('.chat-message[data-mine]')
        ->assertDontSeeIn('.chat-messages', __('chat.ready.outside'))
        ->assertPresent('.chat-messages button:has-text("What can I do here?")')
        ->assertNoSmoke();

    // A report it drafted, sent from outside any project
    $luz = User::where('firstname', 'Luz')->sole();
    app(App\Services\Assistant\Assistant::class)->say(app(ChatService::class)->assistantFor($luz), 'I can tell the team', [
        ['type' => 'report', 'label' => 'Send the report', 'message' => 'I cannot find my cooperative'],
    ]);
    visit(route('account.chat.index'))
        ->click('.chat-actions button:has-text("Send the report")')
        ->assertSeeIn('.chat-actions', 'Sent');
    expect(App\Models\Report::sole())->body->toBe('I cannot find my cooperative')->project_id->toBeNull();
});

/** Someone's first arrival: the Assistant on, and a browser that plays what plays by itself */
function arrivesAt(string $url): object
{
    config(['assistant.driver' => 'api']);

    return tap(visit($url), fn ($page) => $page->script("() => localStorage.setItem('mtav.tours', 'on')"))->refresh();
}

it('greets someone on their first arrival in a project', function () {
    $this->as($this->beto);
    arrivesAt(route('projects.show', $this->project))
        ->assertSeeIn('.chat-window .chat-thread', 'I am the Assistant of this app')
        ->assertNoSmoke();
    expect($this->beto->fresh()->welcomed_at)->not->toBeNull();
});

// Apart from the one in a project: the browser suite serves the app in its own process, where one person's
// project would stay in the next one's links
it('greets someone on their first arrival outside any project', function () {
    $newcomer = aUser(['firstname' => 'Mar']);
    $this->as($newcomer);
    arrivesAt(route('projects.index'))
        ->assertSeeIn('.chat-window .chat-thread', 'I am the Assistant of this app')
        ->assertNoSmoke();
    expect($newcomer->fresh()->welcomed_at)->not->toBeNull();
});

it('drops a count that came with the page once what it counted was read elsewhere', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->post($conversation, $this->ana, 'Hola Beto');
    $this->as($this->beto);

    $page = visit(route('projects.show', $this->project))
        ->assertSeeIn('.chat-launcher-count', '1');

    // Read on another device: this page's count is now stale
    $chat->read($conversation, $this->beto);

    $page->click('.chat-launcher')
        ->assertPresent('.chat-list-item:has-text("Ana Pérez")')
        ->assertMissing('.chat-list-item[data-unread]')
        ->click('.chat-window [aria-label="Minimize"]')
        ->assertMissing('.chat-launcher-count');
});

it('enlarges into the chat\'s page, at the conversation open, and folds the window away', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->post($conversation, $this->ana, 'Hola Beto');
    $this->as($this->beto);

    visit(route('projects.show', $this->project))
        ->click('.chat-launcher')
        ->click('.chat-list-item:has-text("Ana Pérez")')
        ->assertSeeIn('.chat-window .chat-messages', 'Hola Beto')
        ->click('.chat-window [aria-label="Enlarge"]')
        ->assertPathIs('/projects/' . $this->project->id . '/chat/' . $conversation->id)
        ->assertSeeIn('.chat-messages', 'Hola Beto')
        ->assertMissing('.chat-window')
        ->assertNoSmoke();
});

it('edits and deletes one\'s own messages within the hour, by the keyboard too, and nobody else\'s', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->post($conversation, $this->beto, 'Hola Ana');
    $mine = $chat->post($conversation, $this->ana, 'Nos vemos a las 5');
    $this->as($this->ana);

    $page = visit(route('chat.show', [$this->project, $conversation]))
        ->assertSeeIn('.chat-messages', 'Nos vemos a las 5')
        ->assertMissing('.chat-message:not([data-mine]) [aria-label="Edit this message"]')
        ->assertMissing('.chat-message:not([data-mine]) [aria-label="Delete this message"]')
        ->click('.chat-message[data-mine] [aria-label="Edit this message"]')
        ->assertValue('[aria-label="Edit your message"]', 'Nos vemos a las 5')
        ->fill('[aria-label="Edit your message"]', 'Nos vemos a las 6')
        ->keys('[aria-label="Edit your message"]', 'Enter')
        ->assertMissing('[aria-label="Edit your message"]')
        ->assertSeeIn('.chat-messages', 'Nos vemos a las 6');
    expect($mine->fresh()->body)->toBe('Nos vemos a las 6');

    // Escape leaves it as it was, and the focus back on the message
    $page->click('.chat-message[data-mine] [aria-label="Edit this message"]')
        ->fill('[aria-label="Edit your message"]', 'Never mind')
        ->keys('[aria-label="Edit your message"]', 'Escape')
        ->assertMissing('[aria-label="Edit your message"]')
        ->assertSeeIn('.chat-messages', 'Nos vemos a las 6')
        ->assertScript('document.activeElement?.getAttribute("aria-label")', 'Edit this message')
        ->assertNoSmoke();
    assertAccessible($page);
    expect($mine->fresh()->body)->toBe('Nos vemos a las 6');

    // Deleted, it leaves for good
    $page->click('.chat-message[data-mine] [aria-label="Delete this message"]')
        ->assertSeeIn('[role="dialog"]', 'It will disappear for everyone in the conversation.')
        ->click(inDialog('Delete'))
        ->assertDontSeeIn('.chat-messages', 'Nos vemos a las 6')
        ->assertSeeIn('.chat-messages', 'Hola Ana');
    expect($mine->fresh())->toBeNull();
});

it('stops offering to change a message as its hour closes, by itself', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $closing = $chat->post($conversation, $this->ana, 'Almost an hour ago');
    // A little while left: room for the page to load first
    $closing->forceFill(['created_at' => now()->subMinutes(60)->addSeconds(15)])->save();
    $this->as($this->ana);

    // Its last seconds run ahead on the page's clock
    $page = fakeClock(visit(route('chat.show', [$this->project, $conversation])))->refresh()
        ->assertPresent('.chat-message[data-mine] [aria-label="Delete this message"]');
    runClock($page, 16000)
        ->assertMissing('.chat-message[data-mine] [aria-label="Delete this message"]')
        ->assertMissing('.chat-message[data-mine] [aria-label="Edit this message"]');
});

it('deletes a question to the Assistant with its answer, and offers no editing there', function () {
    $conversation = Conversation::where('pair', "assistant:{$this->ana->id}")->sole();
    app(ChatService::class)->post($conversation, $this->ana, 'Where do I see the plan?');
    $this->as($this->ana);

    visit(route('projects.show', $this->project))
        ->click('.chat-launcher')
        ->click('.chat-list-item:has-text("Assistant")')
        ->assertSeeIn('.chat-messages', 'The Assistant is not available here yet')
        ->assertPresent('.chat-message[data-mine] [aria-label="Delete this message"]')
        ->assertMissing('[aria-label="Edit this message"]')
        ->click('.chat-message[data-mine] [aria-label="Delete this message"]')
        ->assertSeeIn('[role="dialog"]', "It will disappear, along with the Assistant's answer to it.")
        ->click(inDialog('Delete'))
        ->assertDontSeeIn('.chat-messages', 'Where do I see the plan?')
        ->assertDontSeeIn('.chat-messages', 'The Assistant is not available here yet')
        ->assertNoSmoke();

    expect($conversation->messages()->count())->toBe(0);
});

it('holds a change while offline, as it holds every change', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $mine = $chat->post($conversation, $this->ana, 'Still here');
    $this->as($this->ana);

    $page = visit(route('chat.show', [$this->project, $conversation]))->assertSeeIn('.chat-messages', 'Still here');
    $page->script('() => window.dispatchEvent(new Event("offline"))');

    $page->click('.chat-message[data-mine] [aria-label="Delete this message"]')
        ->assertSee('You are offline')
        ->assertMissing('[role="dialog"]');
    expect($mine->fresh())->not->toBeNull();
});
