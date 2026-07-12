<?php

use App\Models\User;

uses()->group('Browser.Journeys');

/**
 * Journey 3 — Member life.
 *
 * A member maintains their own account (profile, password — verified by a
 * real re-login — and email change) and browses the community pages:
 * families, members, gallery, events, and the lottery. Lists load async, so
 * every page is awaited before asserting.
 */
test('a member manages their account and browses the community', function () {
    config()->set('app.locale', 'en');

    // Member #102 (family 4, project 1) in the universe fixture
    $this->actingAs(User::find(102));
    setFirstProjectAsCurrent();

    $newEmail = 'e2e-member-new-' . uniqid() . '@example.com';

    // --- Profile edit ---
    visit(route('profile.edit'))
        ->fill('#firstname', 'Journey')
        ->fill('#about', 'Updated by the member journey.')
        ->click('button[type="submit"]')
        ->waitForText('Your profile looks great!')
        ->assertNoSmoke();

    expect(User::find(102)->firstname)->toBe('Journey');

    // --- Password change, proven by a real logout + login ---
    visit(route('password.edit'))
        ->fill('#current_password', 'password')
        ->fill('#password', 'journey-password-1')
        ->fill('#password_confirmation', 'journey-password-1')
        ->click('button[type="submit"]')
        ->waitForText('Great! Your password was updated.');

    // Logout through the user menu (chip shows "Journey 102" after the rename)
    visit(route('dashboard'))
        ->click('Journey 102')
        ->click('Log out')
        ->waitForText('Log in');

    visit(route('login'))
        ->fill('#email', 'member102@example.com')
        ->fill('#password', 'journey-password-1')
        ->click('button[type="submit"]')
        ->waitForText('Dashboard')
        ->assertNoSmoke();

    // --- Email change triggers a verification flow ---
    visit(route('profile.edit'))
        ->fill('#email', $newEmail)
        ->click('button[type="submit"]')
        ->waitForText('Verification email sent');

    expect(User::find(102)->new_email)->toBe($newEmail);

    // --- Community browsing (all lists load async) ---
    visit(route('families.index'))
        ->waitForText('Family 4')
        ->assertNoSmoke();

    visit(route('families.show', 4))
        ->waitForText('Journey')
        ->assertNoSmoke();

    visit(route('members.index'))
        ->waitForText('Journey')
        ->assertNoSmoke();

    visit(route('gallery'))
        ->waitForText('Gallery')
        ->assertNoSmoke();

    visit(route('events.index'))
        ->waitForText('Events')
        ->assertNoSmoke();

    visit(route('lottery'))
        ->waitForText('Lottery')
        ->assertNoSmoke();
});
