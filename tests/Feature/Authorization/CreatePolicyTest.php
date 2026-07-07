<?php

uses()->group('Feature.Authorization');

/**
 * Members cannot create resources: the create policies only allow Admins,
 * and denied access (403) is rendered as a redirect to the Dashboard.
 *
 * Member #102 is an active Member of Project #1.
 */
test('Member cannot access :dataset create page', function ($route) {
    $this->visitRoute($route, asMember: 102, redirects: false)
        ->assertRedirect(route('dashboard'));
})->with([
    'Unit'     => 'units.create',
    'UnitType' => 'unit_types.create',
    'Family'   => 'families.create',
    'Admin'    => 'admins.create',
    'Event'    => 'events.create',
]);

/**
 * Members CAN open the Admin and Member create forms (invitation forms).
 */
test('Member can access :dataset create page', function ($route) {
    $this->visitRoute($route, asMember: 102, redirects: false)
        ->assertOk();
})->with([
    'Member' => 'members.create',
]);

/**
 * The Project create page is gated by the MustBeSuperAdmin middleware,
 * which hides the route (404) from anyone who is not a Superadmin.
 */
test('Non-superadmin users get a 404 on the "Project" create page', function () {
    $this->visitRoute('projects.create', asAdmin: 11, redirects: false)->assertNotFound();
    $this->visitRoute('projects.create', asMember: 102, redirects: false)->assertNotFound();
});

test('Superadmin can access the "Project" create page', function () {
    $this->visitRoute('projects.create', asAdmin: 1, redirects: false)->assertOk();
});
