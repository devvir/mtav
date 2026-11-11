<?php

use App\Models\OpenLottery;
use App\Models\OpenLotteryParticipant;
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

/** The link a page shows in its field of that kind: the organizer's, someone's own, or the one to share */
function linkOn(object $page, string $kind): string
{
    return $page->script(<<<JS
        () => [...document.querySelectorAll('input')].map((input) => input.value)
            .find((value) => ({ organizer: /\\/organize\\//, own: /\\/me\\//, share: /\\/l\\/[^/]+$/ })['{$kind}'].test(value)) ?? ''
        JS);
}

/** Someone joins with the shared link, in their own browser */
function joins(string $link, string $name): object
{
    return visit($link)
        ->fill('input[aria-label="Your name"]', $name)
        ->click('button:has-text("Join")')
        ->assertSee('Your preferences');
}

it('lets people join, rank, come back and leave, the organizer change what is shared out, and keeps its record', function () {
    config(['variant.name' => 'open_lottery']);
    PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, 0);

    $organizer = visit('/')
        ->fill('input[placeholder="Summer house, August"]', 'August')
        ->fill('input[placeholder="Rooms"]', 'Rooms')
        ->fill('textarea:not([aria-hidden])', "Blue room\nGreen room")
        ->click('button:has-text("Create the lottery")')
        ->assertSee('Your organizer link');
    $share = linkOn($organizer, 'share');
    $organizing = linkOn($organizer, 'organizer');
    expect($share)->not->toBe('')->and($organizing)->not->toBe('');

    // The organizer changes what is shared out
    $organizer->fill('input[aria-label="Add one"]', 'Red room')
        ->click('button:has-text("Add")')
        ->assertSee('Red room')
        ->click('[aria-label="Remove Green room"]')
        ->click(inDialog('Remove'))
        ->assertDontSee('Green room');

    // Ana joins and accepts the order as it comes; a room added later asks her to check its place
    $ana = joins($share, 'Ana')->click('button:has-text("This order is fine")')->assertDontSee('This order is fine');
    $own = linkOn($ana, 'own');
    $organizer->fill('input[aria-label="Add one"]', 'Attic')->click('button:has-text("Add")')->assertSee('Attic');
    $ana->refresh()->assertSee('New ones were added since you ranked');

    // Beto joins and leaves on his own; the organizer takes Carla out, and she is told
    joins($share, 'Beto')->click('Leave this lottery')->click(inDialog('Leave'))->assertSee('Join in');
    $carla = joins($share, 'Carla');
    $organizer->refresh()->click('[aria-label="Take Carla out"]')->click(inDialog('Take out'))->assertDontSee('Carla');
    $carla->refresh()->assertSee('The organizer took you out of this lottery');
    joins($share, 'Dora');

    // Ana, from another device by her own link, is herself again
    visit($own)->assertSee('Your preferences')->assertSee('Ana (you)');

    // The organizer, from another device by the organizer link, runs it: two people, two rooms of three
    visit($organizing)
        ->assertSee('Organizing')
        ->assertDontSee('Beto')
        ->click('button:has-text("Run the lottery")')
        ->click('[role=dialog] button:has-text("Run the lottery")')
        ->assertSee('The result')
        ->assertSee('Left over:');

    // Its whole record, for anyone with the link
    $answer = visit($share)->script(<<<'JS'
        async () => {
            const link = [...document.querySelectorAll('a')].find((a) => a.textContent.includes('See the whole record')).href;
            const response = await fetch(link);
            const record = await response.json();
            return { status: response.status, type: response.headers.get('content-type'), keys: Object.keys(record) };
        }
        JS);
    expect($answer['status'])->toBe(200)
        ->and($answer['type'])->toContain('application/json')
        ->and($answer['keys'])->not->toBeEmpty();
});

/** A lottery made on the landing page, of these lines: the organizer's page */
function createsLottery(string $lines): object
{
    config(['variant.name' => 'open_lottery']);

    return visit('/')
        ->fill('input[placeholder="Summer house, August"]', 'August')
        ->fill('input[placeholder="Rooms"]', 'Rooms')
        ->fill('textarea:not([aria-hidden])', $lines)
        ->click('button:has-text("Create the lottery")');
}

it('keeps two quick moves of a ranking, the second after the first', function () {
    $share = linkOn(createsLottery("Blue room\nGreen room\nAttic")->assertSee('Your organizer link'), 'share');

    $ana = watchRequests(joins($share, 'Ana'));
    $ana->keys('[aria-label="Move Attic up"]', ['Enter', 'Enter'])
        ->assertSeeIn('ol.ranking li:first-child', 'Attic');

    $ranking = fn () => OpenLotteryParticipant::firstWhere('name', 'Ana')->ranking()->pluck('name')->all();
    eventually($ana, fn () => expect($ranking())->toBe(['Attic', 'Blue room', 'Green room']));
    // What the page reads again after the save says the same
    answered($ana)->assertSeeIn('ol.ranking li:first-child', 'Attic');
});

it('says which line of what is shared out it refuses, counting the empty ones', function () {
    $long = str_repeat('x', 61);

    createsLottery("Blue room\n\n{$long}\nblue room")
        ->assertSee('Line 3: The name can have up to 60 characters.')
        ->assertSee('Line 1: The name field has a duplicate value.')
        ->assertSee('Line 4: The name field has a duplicate value.');

    expect(OpenLottery::count())->toBe(0);
});

it('says in its own words what is missing, never in the browser\'s', function () {
    config(['variant.name' => 'open_lottery']);
    visit('/')->click('button:has-text("Create the lottery")')->assertSee('Fill in the name.');

    $share = linkOn(createsLottery('Blue room')->assertSee('Your organizer link'), 'share');
    visit($share)->click('button:has-text("Join")')->assertSee('Fill in the name.');
});

it('asks before taking someone out, and takes in its stride what was removed meanwhile', function () {
    $organizer = createsLottery("Blue room\nGreen room")->assertSee('Your organizer link');
    $lottery = OpenLottery::sole();
    $lottery->participants()->create(['name' => 'Beto', 'key' => 'test']);

    $organizer->refresh()
        ->click('[aria-label="Take Beto out"]')
        ->click(inDialog('Cancel'))
        ->assertSee('Beto')
        ->click('[aria-label="Take Beto out"]')
        ->click(inDialog('Take out'))
        ->assertDontSee('Beto');

    // Removed elsewhere, still on this page: removing it again is no error
    $lottery->items()->where('name', 'Green room')->delete();
    $organizer->click('[aria-label="Remove Green room"]')
        ->click(inDialog('Remove'))
        ->assertDontSee('Green room')
        ->assertDontSee('Page not found')
        ->assertSee('Organizing');
});

it('counts one person left with nothing as one, and runs from its question', function () {
    PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, 0);
    $organizer = createsLottery('Blue room')
        ->assertSee('Your organizer link')
        ->fill('input[placeholder="Your name"]', 'Ana')
        ->click('button:has-text("Join")')
        ->assertSee('Your preferences');
    OpenLottery::sole()->participants()->create(['name' => 'Beto', 'key' => 'test']);

    $organizer->refresh()
        ->click('button:has-text("Run the lottery")')
        ->assertSee('1 person will get nothing: there are too few to go round.')
        ->click(inDialog('Run the lottery'))
        ->assertSee('The result');
});
