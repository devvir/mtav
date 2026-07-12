<?php

use App\Models\Family;
use App\Models\Member;
use App\Models\UnitType;
use App\Models\User;

uses()->group('Browser.Journeys');

/**
 * Journey 2 — Admin setup.
 *
 * An admin builds the project structure (unit type → unit → family) and
 * onboards people: invites a member into the new family and a fellow admin.
 * The invited member completes registration and lands in the app.
 */
test('an admin builds project structure and onboards people', function () {
    config()->set('app.locale', 'en');

    // Admin #11 manages project 1 in the universe fixture
    $this->actingAs(User::find(11));
    setFirstProjectAsCurrent();

    $typeName = 'E2E Unit Type ' . uniqid();
    $unitIdentifier = 'E2E-UNIT-' . uniqid();
    $familyName = 'E2E Family ' . uniqid();
    $memberEmail = 'e2e-member-' . uniqid() . '@example.com';
    $adminEmail = 'e2e-second-admin-' . uniqid() . '@example.com';

    // --- Structure: unit type → unit → family ---
    visit(route('unit_types.create'))
        ->fill('input[name="name"]', $typeName)
        ->fill('input[name="description"]', 'Two-bedroom units created by the journey.')
        ->click('button[type="submit"]')
        ->waitForText('New Unit Type is ready!');

    $unitType = UnitType::where('name', $typeName)->firstOrFail();

    visit(route('units.create'))
        ->select('select[name="unit_type_id[]"]', (string) $unitType->id)
        ->fill('input[name="identifier"]', $unitIdentifier)
        ->click('button[type="submit"]')
        ->waitForText('New Unit is ready and added!');

    // Admin #11 manages a single project, so create forms don't render a
    // project selector (unlike superadmins, who see all projects)
    visit(route('families.create'))
        ->fill('input[name="name"]', $familyName)
        ->select('select[name="unit_type_id[]"]', (string) $unitType->id)
        ->click('button[type="submit"]')
        ->waitForText('New Family added to the community!');

    $family = Family::where('name', $familyName)->firstOrFail();
    expect($family->unit_type_id)->toBe($unitType->id);

    // --- People: invite a member into the new family, and a fellow admin ---
    $memberInviteUrl = captureInvitationUrl(function () use ($family, $memberEmail) {
        visit(route('members.create'))
            ->select('select[name="family_id[]"]', (string) $family->id)
            ->fill('input[name="email"]', $memberEmail)
            ->fill('input[name="firstname"]', 'Journey')
            ->fill('input[name="lastname"]', 'Member')
            ->click('button[type="submit"]')
            ->waitForText('Member invitation sent out!');
    });

    captureInvitationUrl(function () use ($adminEmail) {
        visit(route('admins.create'))
            ->fill('input[name="email"]', $adminEmail)
            ->fill('input[name="firstname"]', 'Second')
            ->fill('input[name="lastname"]', 'Admin')
            ->click('button[type="submit"]')
            ->waitForText('Admin invitation is on its way!');
    });

    // --- The invited member completes registration (session switches) ---
    visit($memberInviteUrl)
        ->waitForText('Complete Registration')
        ->fill('#password', 'member-secret-123')
        ->fill('#password_confirmation', 'member-secret-123')
        ->click('button[type="submit"]')
        ->waitForText("Welcome aboard! You're all set.")
        ->assertNoSmoke();

    $member = Member::where('email', $memberEmail)->firstOrFail();
    expect($member->completedRegistration())->toBeTrue()
        ->and($member->family_id)->toBe($family->id)
        ->and(User::where('email', $adminEmail)->exists())->toBeTrue();
});
