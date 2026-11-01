<?php

/**
 * A list's filters are words. An address with a list of them instead, which no page links
 * to, shows the list unfiltered rather than failing.
 */

it('shows a console list unfiltered given a list for a word', function (string $list, string $filter) {
    $this->as(aSuperadmin())->get(route($list, [$filter => ['x', 'y']]))->assertOk();
})->with([
    ['admin.activity.index', 'q'],
    ['admin.activity.index', 'kind'],
    ['admin.projects.index', 'q'],
    ['admin.users.index', 'q'],
]);

it('shows a project\'s list unfiltered given a list for a word', function (string $list, string $filter) {
    $project = aProject();

    $this->as(anAdminOf($project), $project)->get(route($list, [$filter => ['x', 'y']]))->assertOk();
})->with([
    ['events.index', 'q'],
    ['forum.index', 'q'],
    ['media.index', 'q'],
    ['media.index', 'category'],
    ['people.index', 'q'],
]);

it('shows the directory unfiltered given a list for a word', function () {
    $this->as(aUser())->get(route('directory.index', ['q' => ['x', 'y']]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.q', null));
});

it('still narrows a list by a word', function () {
    aProject(['name' => 'Los Ceibos']);
    aProject(['name' => 'Las Acacias']);

    $this->as(aSuperadmin())->get(route('admin.projects.index', ['q' => 'Ceibos']))
        ->assertInertia(fn ($page) => $page->where('filters.q', 'Ceibos')->has('projects.data', 1));
});
