<?php

use App\Models\Conversation;
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
    $chat->send($conversation, $this->ana, 'Hola Beto');
    $chat->send($conversation, $this->beto, 'Hola Ana');
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

it('starts a named group for several people', function () {
    $carla = aMemberOf(aFamily($this->project), ['firstname' => 'Carla', 'lastname' => 'Díaz']);
    $this->as($this->ana);

    visit(route('chat.index', $this->project))
        ->click('New conversation')
        ->click('[role="dialog"] input[role="combobox"]')
        ->click('[role="option"]:has-text("Beto Silva")')
        ->click('[role="dialog"] input[role="combobox"]')
        ->click('[role="option"]:has-text("Carla Díaz")')
        ->fill('[role="dialog"] input[maxlength="80"]', 'Building committee')
        ->click(inDialog('Start the group'))
        ->assertSeeIn('.chat-thread-header', 'Building committee');

    expect(Conversation::where('is_community', false)->where('is_assistant', false)->sole())->is_group->toBeTrue()->title->toBe('Building committee');
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
    $chat->send($conversation, $this->beto, 'Hola Ana');
    $this->as($this->ana);

    $page = visit(route('chat.show', [$this->project, $conversation]))->assertSee('Hola Ana');
    $page->script('() => {
        window.realFetch = window.fetch;
        window.fetch = () => Promise.reject(new TypeError("Failed to fetch"));
    }');

    $page->fill('[aria-label="Message"]', 'Sin conexión')
        ->keys('[aria-label="Message"]', 'Enter')
        ->assertSee('That did not work. Please try again.')
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

    $page->click('.chat-window [aria-label="Minimize"]')->wait(0.3)->assertMissing('.chat-window');
    expect($page->script($onBubble))->toBeTrue();

    // By the keyboard too
    $page->script('() => document.querySelector(".chat-launcher").click()');
    $page->assertPresent('.chat-window')->keys('.chat-window', 'Escape')->wait(0.3)->assertMissing('.chat-window');
    expect($page->script($onBubble))->toBeTrue();
});

it('says which conversations are unread to whoever cannot see the mark', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->send($conversation, $this->ana, 'Hola Beto');
    $this->as($this->beto);

    visit(route('chat.index', $this->project))
        ->assertPresent('.chat-list-item .chat-unread')
        ->assertSeeIn('.chat-list-item', 'Unread');
});

it('writes each message\'s time in the app\'s language, whatever the browser\'s', function () {
    $chat = app(ChatService::class);
    $conversation = $chat->start($this->project, $this->ana, [$this->beto->id]);
    $chat->send($conversation, $this->ana, 'Hola Beto');
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

    // Its own page, where phones have it: the same, on its own
    visit(route('account.chat.index'))
        ->assertSeeIn('.chat-messages', __('chat.ready.outside'))
        ->assertMissing('.chat-list')
        ->assertNoSmoke();
});
