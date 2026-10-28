<?php

use App\Models\User;

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

    $details = route('people.index', [$this->project, 'person' => $neighbour->id]);
    $asMember = $this->as($this->member, $this->project)->get($details)->inertiaProps('person.person');
    $asAdmin = $this->as($this->admin, $this->project)->get($details)->inertiaProps('person.person');

    expect($asMember)->not->toHaveKey('email')
        ->and($asAdmin['email'])->toBe('neighbour@example.com');
});

it('opens someone\'s details over the people list, where their address leads', function () {
    $this->as($this->member, $this->project)
        ->get(route('people.show', $this->member))
        ->assertRedirect(route('people.index', ['person' => $this->member->id]));

    expect($this->as($this->member, $this->project)
        ->get(route('people.index', ['person' => $this->otherMember->id]))
        ->inertiaProps('person'))->toBeNull();
});

it('finds people by email only for admins, so that nobody learns who has an address', function () {
    config()->set('lists.whole', 0); // the server searches and filters (long lists)

    aMemberOf(aFamily($this->project), ['firstname' => 'Neighbour', 'email' => 'secret.neighbour@example.com']);
    $search = route('people.index', [$this->project, 'q' => 'secret.neighbour']);

    expect($this->as($this->member, $this->project)->get($search)->inertiaProps('people.data'))->toBeEmpty()
        ->and(collect($this->as($this->admin, $this->project)->get($search)->inertiaProps('people.data'))->pluck('name')->first())
        ->toStartWith('Neighbour');

    $families = route('families.index', [$this->project, 'q' => 'secret.neighbour']);
    expect($this->as($this->member, $this->project)->get($families)->inertiaProps('families.data'))->toBeEmpty();
});

it('filters people by family and role, and families by unit type', function () {
    config()->set('lists.whole', 0); // the server searches and filters (long lists)

    $listed = function (string $list, array $query, string $key) {
        $response = $this->as($this->member, $this->project)->get(route($list, [$this->project, ...$query]));
        $response->assertOk();

        return collect($response->inertiaProps("{$key}.data"))->pluck('id')->all();
    };

    $family = $this->member->familyIn($this->project);

    expect($listed('people.index', ['family' => $family->id], 'people'))->toContain($this->member->id)
        ->not->toContain($this->admin->id)
        ->and($listed('people.index', ['admins' => 1], 'people'))->toBe([$this->admin->id])
        ->and($listed('families.index', ['unit_type' => $family->unit_type_id], 'families'))->toContain($family->id);
});

it('shows and finds the email and phone people choose to share, and admins see them all', function () {
    config()->set('lists.whole', 0); // the server searches and filters (long lists)

    $sharing = aMemberOf(aFamily($this->project), [
        'firstname' => 'Sharing', 'email' => 'open@example.com', 'phone' => '099 111 222', 'email_public' => true, 'phone_public' => true,
    ]);
    $private = aMemberOf(aFamily($this->project), ['firstname' => 'Private', 'email' => 'closed@example.com', 'phone' => '099 333 444']);
    $details = fn (User $viewer, User $person) => $this->as($viewer, $this->project)
        ->get(route('people.index', [$this->project, 'person' => $person->id]))->inertiaProps('person.person');
    $found = fn (User $viewer, string $q) => collect($this->as($viewer, $this->project)
        ->get(route('people.index', [$this->project, 'q' => $q]))->inertiaProps('people.data'))->pluck('id')->all();

    expect($details($this->member, $sharing))->toMatchArray(['email' => 'open@example.com', 'phone' => '099 111 222'])
        ->and($details($this->member, $private))->not->toHaveKeys(['email', 'phone'])
        ->and($details($this->admin, $private))->toMatchArray(['email' => 'closed@example.com', 'phone' => '099 333 444'])
        ->and($found($this->member, 'open@'))->toBe([$sharing->id])
        ->and($found($this->member, 'closed@'))->toBe([])
        ->and($found($this->admin, 'closed@'))->toBe([$private->id]);
});

it('sends short lists whole, for the browser to search, without what the person may not see', function () {
    aMemberOf(aFamily($this->project), ['firstname' => 'Private', 'email' => 'closed@example.com']);

    $response = $this->as($this->member, $this->project)
        ->get(route('people.index', [$this->project, 'q' => 'nobody matches this']));

    expect($response->inertiaProps('whole'))->toBeTrue()
        ->and(collect($response->inertiaProps('people.data'))->count())->toBe($this->project->members()->count())
        ->and(collect($response->inertiaProps('people.data'))->pluck('email')->filter()->all())
        ->not->toContain('closed@example.com');
});
