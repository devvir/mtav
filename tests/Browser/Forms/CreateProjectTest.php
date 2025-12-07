<?php

use App\Models\Admin;
use App\Models\Project;
use App\Models\User;

uses()->group('Browser.Forms');

beforeEach(function () {
    $this->actingAs(User::find(1));

    config()->set('app.locale', 'en');
});

test('Create Project form is reachable and displays expected fields', function () {
    visit(route('projects.create'))
        ->screenshot(filename: 'projects-create-form')
        ->assertPresent('input[name="name"]')
        ->assertPresent('input[name="description"]')
        ->assertPresent('input[name="organization"]')
        ->assertPresent('select[name="admins[]"]')
        ->assertNoSmoke();
});

test('Can create a Project (happy path) with existing Admins', function () {
    $name = 'E2E Project ' . uniqid();
    $description = 'A valid project description for e2e tests.';

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-create')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', $description)
        ->fill('input[name="organization"]', 'Acme Corp')
        // select Admin 11 (exists in universe.sql)
        ->select('select[name="admins[]"]', '11')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-create');

    expect(Project::where('name', $name))->toExist();
});

test('Can create a Project by inviting a new Admin (happy path)', function () {
    $name = 'E2E Project Invite ' . uniqid();
    $description = 'Invite admin flow for e2e tests.';
    $email = 'e2e-invite-' . uniqid() . '@example.com';

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-create-invite')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', $description)
        ->fill('input[name="new_admin_email"]', $email)
        ->fill('input[name="organization"]', 'Acme Corp')
        ->fill('input[name="new_admin_firstname"]', 'Invited')
        ->fill('input[name="new_admin_lastname"]', 'User')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-create-invite');

    expect(Project::where('name', $name))->toExist();
    expect(User::where('email', $email))->toExist();
});

test('Validation: name must be unique', function () {
    // Project 1 exists in the universe fixture
    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-create-validation-unique')
        ->fill('input[name="name"]', 'Project 1')
        ->fill('input[name="description"]', 'Valid description')
        ->fill('input[name="organization"]', 'Acme Corp')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-create-validation-unique')
        ->assertSee('The Name has already been taken');
});

// ---------- Admin assignment cases ----------

test('Admin assignment: create project with no admins selected', function () {
    $name = 'E2E No Admins ' . uniqid();

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-no-admins')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', 'No admins test')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-no-admins')
        ->assertSee($name);

    expect($project = Project::firstWhere('name', $name))->not->toBeNull();
    expect($project->admins->pluck('id')->toArray())->toBe([]);
});

test('Admin assignment: pick one admin from dropdown', function () {
    $name = 'E2E One Admin ' . uniqid();

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-one-admin')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', 'One admin test')
        ->fill('input[name="organization"]', 'Acme Corp')
        ->select('select[name="admins[]"]', '11')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-one-admin')
        ->assertSee($name);

    expect($project = Project::firstWhere('name', $name))->not->toBeNull();
    expect($project->admins->pluck('id')->sort()->values()->toArray())->toBe([11]);
});

test('Admin assignment: pick two admins from dropdown', function () {
    $name = 'E2E Two Admins ' . uniqid();

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-two-admins')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', 'Two admins test')
        ->fill('input[name="organization"]', 'Acme Corp')
        ->select('select[name="admins[]"]', ['11', '12'])
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-two-admins')
        ->assertSee($name);

    expect($project = Project::firstWhere('name', $name))->not->toBeNull();
    expect($project->admins->pluck('id')->sort()->values()->toArray())->toBe([11, 12]);
});

test('Admin assignment: create new admin while selecting none (valid)', function () {
    $name = 'E2E Create With New Admin ' . uniqid();
    $email = 'e2e-newadmin-' . uniqid() . '@example.com';

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-create-new-admin')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', 'Create with new admin')
        ->fill('input[name="organization"]', 'Acme Corp')
        ->fill('input[name="new_admin_email"]', $email)
        ->fill('input[name="new_admin_firstname"]', 'Newbie')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-create-new-admin')
        ->assertSee($name);

    expect($project = Project::firstWhere('name', $name))->not->toBeNull();
    expect($project->admins()->pluck('email')->toArray())->toBe([$email]);

});

test('Admin assignment: create new admin without firstname fails', function () {
    $name = 'E2E New Admin Missing Firstname ' . uniqid();
    $email = 'e2e-newadmin-missing-first-' . uniqid() . '@example.com';

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-create-new-admin-missing-first')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', 'Missing firstname')
        ->fill('input[name="new_admin_email"]', $email)
        ->fill('input[name="organization"]', 'Acme Corp')
        // no firstname
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-create-new-admin-missing-first')
        ->assertSee('The New Admin Firstname field is required when New Admin Email is present');

    expect(Project::where('name', $name))->not->toExist();
    expect(User::where('email', $email))->not->toExist();
});

test('Admin assignment: create new admin without email fails', function () {
    $name = 'E2E New Admin Missing Email ' . uniqid();

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-create-new-admin-missing-email')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', 'Missing email')
        ->fill('input[name="new_admin_firstname"]', 'NoEmail')
        ->fill('input[name="organization"]', 'Acme Corp')
        // no email
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-create-new-admin-missing-email')
        ->assertSee('The new admin email field is required when new admin firstname is present.');

    expect(Project::where('name', $name))->not->toExist();
});

test('Admin assignment: pick one existing and add a new admin', function () {
    $name = 'E2E One Plus New Admin ' . uniqid();
    $email = 'e2e-one-plus-new-' . uniqid() . '@example.com';

    visit(route('projects.create'))
        ->screenshot(filename: 'before:projects-one-plus-new')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', 'One plus new admin')
        ->fill('input[name="organization"]', 'Acme Corp')
        ->select('select[name="admins[]"]', '11')
        ->fill('input[name="new_admin_email"]', $email)
        ->fill('input[name="new_admin_firstname"]', 'Plus')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:projects-one-plus-new')
        ->assertSee($name);

    expect($project = Project::firstWhere('name', $name))->not->toBeNull();
    expect($newAdmin = Admin::firstWhere('email', $email))->not->toBeNull();

    expect($project->admins()->pluck('email', 'users.id')->sort()->toArray())->toBe([
        11            => Admin::find(11)->email,
        $newAdmin->id => $email,
    ]);
});
