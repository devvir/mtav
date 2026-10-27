<?php

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
