<?php

uses()->group('Feature.Authorization');

test('Member cannot access :dataset create page', function ($route) {
    $this->visitRoute($route, asMember: 100, redirects: false)
        ->assertRedirect(route('login'));
})->with([
    'Project'  => 'projects.create',
    'Unit'     => 'units.create',
    'UnitType' => 'unit_types.create',
    'Family'   => 'families.create',
    'Admin'    => 'admins.create',
    'Event'    => 'events.create',
]);

test('Non-superadmin Admin cannot access "Project" create page', function () {
    $this->visitRoute('projects.create', asAdmin: 11, redirects: false)->assertNotFound();
});
