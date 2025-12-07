<?php

// Copilot - Pending review

use App\Models\Admin;
use App\Models\Project;
use App\Models\User;

uses()->group('Browser.Forms');

beforeEach(function () {
    $this->actingAs(User::find(1));
    // Default to single-project context for tests that require it.
    setFirstProjectAsCurrent();

    config()->set('app.locale', 'en');
});

test('Create Admin form is reachable and displays expected fields', function () {
    visit(route('admins.create'))
        ->screenshot(filename: 'admins-create-form')
        ->assertPresent('select[name="project_ids[]"]')
        ->assertPresent('input[name="email"]')
        ->assertPresent('input[name="firstname"]')
        ->assertPresent('input[name="lastname"]')
        ->assertNoSmoke();
});

test('Multi-project: form shows project select when no project selected', function () {
    // Ensure no project selected to simulate multi-project context
    resetCurrentProject();

    visit(route('admins.create'))
        ->screenshot(filename: 'admins-create-form-multiproject')
        ->assertPresent('select[name="project_ids[]"]')
        ->assertNoSmoke();
});

test('Multi-project: cannot submit without selecting a project', function () {
    resetCurrentProject();

    $email = 'e2e-admin-no-project-' . uniqid() . '@example.com';

    visit(route('admins.create'))
        ->screenshot(filename: 'before:admins-create-no-project')
        ->fill('input[name="email"]', $email)
        ->fill('input[name="firstname"]', 'E2E')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:admins-create-no-project');

    // Ensure the admin was not created because project selection is required
    expect(Admin::where('email', $email))->not->toExist();
});

test('Can create an Admin (happy path)', function () {
    $email = 'e2e-admin-' . uniqid() . '@example.com';

    visit(route('admins.create'))
        ->screenshot(filename: 'before:admins-create')
        ->select('select[name="project_ids[]"]', '1')
        ->fill('input[name="email"]', $email)
        ->fill('input[name="firstname"]', 'E2E')
        ->fill('input[name="lastname"]', 'Admin')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:admins-create');

    expect(Admin::where('email', $email))->toExist();
});

test('Validation: email must be unique', function () {
    visit(route('admins.create'))
        ->screenshot(filename: 'before:admins-create-validation-unique')
        ->select('select[name="project_ids[]"]', '1')
        ->fill('input[name="email"]', 'admin11@example.com')
        ->fill('input[name="firstname"]', 'Existing')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:admins-create-validation-unique')
        ->assertSee('The Email has already been taken');
});
