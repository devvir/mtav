<?php

use App\Models\OpenLottery;
use App\Models\PlatformSetting;

/**
 * The whole of an open lottery, as its organizer sees it: from the landing page to the result.
 */

it('goes from a landing page to a result, with nothing to sign in to', function () {
    config(['variant.name' => 'open_lottery']);
    PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, 0);

    $page = visit('/')->assertSee('Share things out fairly');

    $page->fill('input[placeholder="Summer house, August"]', 'August')
        ->fill('input[placeholder="Rooms"]', 'Rooms')
        ->fill('textarea:not([aria-hidden])', "Blue room\nGreen room")
        ->click('button:has-text("Create the lottery")')
        ->assertSee('Link to share')
        ->assertSee('Your organizer link')
        ->fill('input[placeholder="Your name"]', 'Ana')
        ->click('button:has-text("Join")')
        ->assertSee('Your preferences')
        ->assertSee('Your own link');

    $lottery = OpenLottery::sole();
    $lottery->participants()->create(['name' => 'Beto', 'key' => 'test']);

    // Someone with only the link sees who is in, and nothing to run it with
    visit("/l/{$lottery->slug}")->assertSee('Beto')->assertSee('Join in')->assertDontSee('Run the lottery');

    $page->refresh()
        ->assertSee('Beto')
        ->click('button:has-text("Run the lottery")')
        ->assertSee('Run it like this?')
        ->click('[role=dialog] button:has-text("Run the lottery")')
        ->assertSee('The result')
        ->assertSee('See the whole record');
});
