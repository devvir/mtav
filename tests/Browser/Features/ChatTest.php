<?php

use App\Models\Conversation;

/**
 * The chat: starting a conversation, writing in it, and the other person finding it
 * waiting for them, marked unread until they open it.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->ana = aMemberOf(aFamily($this->project), ['firstname' => 'Ana', 'lastname' => 'Pérez']);
    $this->beto = aMemberOf(aFamily($this->project), ['firstname' => 'Beto', 'lastname' => 'Silva']);
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

    expect(Conversation::where('is_community', false)->sole()->messages()->count())->toBe(1);
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

    expect(Conversation::where('is_community', false)->sole())->is_group->toBeTrue()->title->toBe('Building committee');
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
