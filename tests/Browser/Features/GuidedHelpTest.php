<?php

uses()->group('critical');

/**
 * Info icons explain what needs explaining, and "Show me how" walks through it.
 */
it('explains the lottery and walks a member through their ranking', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    aUnit($type, ['identifier' => 'A-102']);
    $member = aMemberOf(aFamily($project, $type));

    $this->as($member);

    visit(route('lottery.show', $project))
        ->click('[aria-label="About: How the lottery works"]')
        ->assertSee('only ties are drawn at random')
        ->click('Show me how')
        ->assertSee('Step 1 of 4')
        ->assertSee('These are the units your family competes for')
        ->click('Next')
        ->assertSee('Step 2 of 4')
        ->keys('[role="dialog"][aria-modal="true"]', 'Escape')
        ->assertDontSee('Step 2 of 4')
        ->assertNoSmoke();
});

it('closes an explanation once read', function () {
    $project = aProject();
    $this->as(aMemberOf(aFamily($project, aUnitType($project))));

    visit(route('lottery.show', $project))
        ->click('[aria-label="About: How the lottery works"]')
        ->assertSee('only ties are drawn at random')
        ->click('Got it')
        ->assertDontSee('only ties are drawn at random');
});

it('goes back and forth through a tour, and ends it at any step', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    visit(route('lottery.show', $project))
        ->click('[aria-label="About: How the lottery works"]')
        ->click('Show me how')
        ->click('Next')
        ->assertSee('Step 2 of 4')
        ->click('Back')
        ->assertSee('Step 1 of 4')
        ->click('[aria-label="End the tour"]')
        ->assertDontSee('Step 1 of 4');
});

it('finishes a tour on its last step', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    visit(route('lottery.show', $project))
        ->click('[aria-label="About: How the lottery works"]')
        ->click('Show me how')
        ->click('Next')
        ->click('Next')
        ->click('Next')
        ->assertSee('Step 4 of 4')
        ->click('Done')
        ->assertDontSee('Step 4 of 4');
});
