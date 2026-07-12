<?php

use App\Models\Admin;
use App\Models\Project;

uses()->group('Browser.Journeys');

/**
 * Journey 1 — Provisioning.
 *
 * A superadmin logs in through the real login form and creates a new project,
 * inviting its first admin inline. The invited admin follows the emailed link
 * and completes registration, landing authenticated in the new project.
 */
test('superadmin provisions a project and its first admin, end to end', function () {
    config()->set('app.locale', 'en');

    $projectName = 'E2E Cooperative ' . uniqid();
    $adminEmail = 'e2e-first-admin-' . uniqid() . '@example.com';

    // --- Superadmin logs in through the real login form ---
    visit(route('login'))
        ->fill('#email', 'superadmin1@example.com')
        ->fill('#password', 'password')
        ->click('button[type="submit"]')
        ->waitForText('Projects')
        ->assertNoSmoke();

    // --- Creates a project, inviting its first admin inline ---
    $invitationUrl = captureInvitationUrl(function () use ($projectName, $adminEmail) {
        visit(route('projects.create'))
            ->assertPresent('input[name="new_admin_email"]')
            ->fill('input[name="name"]', $projectName)
            ->fill('input[name="description"]', 'Created by the provisioning journey.')
            ->fill('input[name="organization"]', 'E2E Org')
            ->fill('input[name="new_admin_email"]', $adminEmail)
            ->fill('input[name="new_admin_firstname"]', 'Prima')
            ->fill('input[name="new_admin_lastname"]', 'Admin')
            ->click('button[type="submit"]')
            ->waitForText($projectName);
    });

    $project = Project::where('name', $projectName)->firstOrFail();
    $admin = Admin::where('email', $adminEmail)->firstOrFail();
    expect($admin->isInvited())->toBeTrue()
        ->and($admin->projects()->pluck('projects.id')->all())->toContain($project->id);

    // --- The invited admin follows the emailed link (token authenticates
    //     the session) and completes registration ---
    visit($invitationUrl)
        ->waitForText('Complete Registration')
        ->fill('#password', 'brand-new-secret-1')
        ->fill('#password_confirmation', 'brand-new-secret-1')
        ->click('button[type="submit"]')
        ->waitForText($projectName)
        ->assertNoSmoke();

    expect($admin->refresh()->completedRegistration())->toBeTrue();
});
