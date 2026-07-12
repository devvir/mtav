<?php

use App\Models\User;

uses()->group('Browser.Journeys');

/**
 * Journey 5 — Authorization boundaries.
 *
 * The access-control safety net: three kinds of user hit routes they must not
 * reach, and each is deflected the way the app intends. Breaking any of these
 * silently would be a serious regression, so they get an explicit E2E guard.
 */
test('the app deflects unauthorized access for guests, members, and invitees', function () {
    config()->set('app.locale', 'en');

    // --- A guest is bounced to the login page ---
    visit(route('families.index'))
        ->assertPathIs('/login')
        ->assertSee('Log in')
        ->assertNoSmoke();

    // --- A member cannot reach admin-only management (policy denial ->
    //     silent redirect to the dashboard, per bootstrap/app.php) ---
    $this->actingAs(User::find(102)); // member, project 1
    setFirstProjectAsCurrent();

    visit(route('admins.create'))
        ->assertPathIs('/')
        ->waitForText('Dashboard')
        ->assertNoSmoke();

    visit(route('unit_types.create'))
        ->assertPathIs('/')
        ->assertNoSmoke();

    // --- An invited-but-unregistered user is forced back to the invitation
    //     flow no matter which route they try (HandleInvitedUsers) ---
    auth()->logout();
    $invited = User::where('email', 'invited18@example.com')->firstOrFail();
    expect($invited->completedRegistration())->toBeFalse();

    $this->actingAs($invited);

    visit(route('dashboard'))
        ->assertPathIs('/invitation')
        ->waitForText('Complete Registration')
        ->assertNoSmoke();

    visit(route('families.index'))
        ->assertPathIs('/invitation')
        ->assertNoSmoke();
});
