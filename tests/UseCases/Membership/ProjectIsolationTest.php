<?php

/**
 * Nobody sees or reaches another project's data unless they take part in it.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->member = aMemberOf(aFamily($this->project));
    $this->admin = anAdminOf($this->project);

    $this->other = aProject();
    $this->otherType = aUnitType($this->other);
    $this->otherFamily = aFamily($this->other, $this->otherType);
    $this->otherMember = aMemberOf($this->otherFamily);
    $this->otherUnit = aUnit($this->otherType);
    $this->otherEvent = anEvent($this->other);
});

dataset('outsiders', [
    'a member of another project' => fn () => $this->member,
    'an admin of another project' => fn () => $this->admin,
]);

it('hides another project\'s pages from :dataset', function ($user) {
    $pages = [
        route('projects.show', $this->other),
        route('families.show', [$this->other, $this->otherFamily]),
        route('people.index', $this->other),
        route('people.show', [$this->other, $this->otherMember]),
        route('units.show', [$this->other, $this->otherUnit]),
        route('events.show', [$this->other, $this->otherEvent]),
        route('lottery.show', $this->other),
    ];

    foreach ($pages as $page) {
        expect($this->as($user)->get($page))->toBeDenied();
    }
})->with('outsiders');

it('does not reach another project\'s records through their own project for :dataset', function ($user) {
    $pages = [
        route('families.show', [$this->project, $this->otherFamily]),
        route('people.show', [$this->project, $this->otherMember]),
        route('units.show', [$this->project, $this->otherUnit]),
        route('events.show', [$this->project, $this->otherEvent]),
    ];

    foreach ($pages as $page) {
        expect($this->as($user)->get($page))->toBeNotFound();
    }
})->with('outsiders');

it('lists only the project\'s own members', function () {
    $listed = $this->as($this->admin, $this->project)->get(route('people.index'))->inertiaProps('people.data');

    expect(collect($listed)->pluck('id'))
        ->toContain($this->member->id)
        ->not->toContain($this->otherMember->id);
});

it('keeps contact details from fellow members, but shows them to admins', function () {
    $neighbour = aMemberOf(aFamily($this->project), ['email' => 'neighbour@example.com']);

    $asMember = $this->as($this->member, $this->project)->get(route('people.show', $neighbour))->inertiaProps('person');
    $asAdmin = $this->as($this->admin, $this->project)->get(route('people.show', $neighbour))->inertiaProps('person');

    expect($asMember)->not->toHaveKey('email')
        ->and($asAdmin['email'])->toBe('neighbour@example.com');
});
