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
        ->assertSee('No conversations yet')
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
        ->assertSeeIn('.shell-nav', 'Messages')
        ->click('.shell-nav a:has-text("Messages")')
        ->assertPresent('.chat-list-item[data-unread]')
        ->click('.chat-list-item:has-text("Ana Pérez")')
        ->assertSeeIn('.chat-messages', 'Hola Beto, ¿vienes el sábado?')
        ->assertMissing('.chat-list-item[data-unread]');

    expect(Conversation::sole()->messages()->count())->toBe(1);
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

    expect(Conversation::sole())->is_group->toBeTrue()->title->toBe('Building committee');
});
