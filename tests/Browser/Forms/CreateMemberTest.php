<?php

// Copilot - Pending review

use App\Models\User;

uses()->group('Browser.Forms');

beforeEach(function () {
    $this->actingAs(User::find(1));
    // Default to single-project context for tests that require it.
    setFirstProjectAsCurrent();

    config()->set('app.locale', 'en');
});

test('Create Member form is reachable and displays expected fields', function () {
    visit(route('members.create'))
        ->screenshot(filename: 'members-create-form')
        ->assertPresent('select[name="project_id[]"]')
        ->assertPresent('select[name="family_id[]"]')
        ->assertPresent('input[name="email"]')
        ->assertPresent('input[name="firstname"]')
        ->assertNoSmoke();
});

test('Multi-project: form shows project select when no project selected', function () {
    resetCurrentProject();

    visit(route('members.create'))
        ->screenshot(filename: 'members-create-form-multiproject')
        ->assertPresent('select[name="project_id[]"]')
        ->assertNoSmoke();
});

test('Multi-project: cannot submit without selecting a project', function () {
    resetCurrentProject();

    $email = 'e2e-member-no-project-' . uniqid() . '@example.com';

    visit(route('members.create'))
        ->screenshot(filename: 'before:members-create-no-project')
        ->fill('input[name="email"]', $email)
        ->fill('input[name="firstname"]', 'E2E')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:members-create-no-project');

    expect(User::where('email', $email))->not->toExist();
});

test('Can create a Member (happy path)', function () {
    $email = 'e2e-member-' . uniqid() . '@example.com';

    visit(route('members.create'))
        ->screenshot(filename: 'before:members-create')
        ->select('select[name="project_id[]"]', '1')
        // family 4 belongs to project 1 in the fixture
        ->select('select[name="family_id[]"]', '4')
        ->fill('input[name="email"]', $email)
        ->fill('input[name="firstname"]', 'E2E')
        ->fill('input[name="lastname"]', 'Member')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:members-create');

    expect(User::where('email', $email))->toExist();
});

test('Validation: email must be unique for members', function () {
    visit(route('members.create'))
        ->screenshot(filename: 'before:members-create-validation-unique')
        ->select('select[name="project_id[]"]', '1')
        ->select('select[name="family_id[]"]', '4')
        ->fill('input[name="email"]', 'admin11@example.com')
        ->fill('input[name="firstname"]', 'Existing')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:members-create-validation-unique')
        ->assertSee('The Email has already been taken');
});
