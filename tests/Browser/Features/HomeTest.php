<?php

/**
 * A project's home: its figures, the lottery up front once it has a date, what was shared
 * and what is coming up side by side, and panels each person folds as they like.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->type = aUnitType($this->project);
    aUnit($this->type, ['identifier' => 'A-101']);
    $this->member = aMemberOf(aFamily($this->project, $this->type));
    $this->as($this->member);
});

it('leaves the lottery out until it has a date', function () {
    visit(route('projects.show', $this->project))
        ->assertSee('Recently shared')
        ->assertNotPresent('.lottery-banner');
});

it('puts the lottery up front once it has a date, leading to it', function () {
    $this->project->lottery->update(['start_date' => now()->addWeek()]);

    visit(route('projects.show', $this->project))
        ->assertPresent('.lottery-banner')
        ->assertSeeIn('.lottery-banner', 'Scheduled for')
        ->click('.lottery-banner')
        ->assertPathIs('/projects/' . $this->project->id . '/lottery');
});

it('shows what was shared and what is coming up side by side', function () {
    $page = visit(route('projects.show', $this->project))->resize(1440, 900);

    $tops = $page->script("() => [...document.querySelectorAll('.dashboard-pair > .ds-panel')].map((panel) => panel.getBoundingClientRect().top)");
    expect($tops)->toHaveCount(2)->and($tops[0])->toBe($tops[1]);
});

it('folds a panel, and keeps it folded on the next visit', function () {
    $page = visit(route('projects.show', $this->project))
        ->click('.dashboard-pair .ds-panel:first-child [aria-label="Fold"]')
        ->assertDontSee('Nothing shared yet');

    $page->refresh()
        ->assertPresent('.dashboard-pair .ds-panel:first-child [aria-label="Unfold"]')
        ->assertDontSee('Nothing shared yet');
});
