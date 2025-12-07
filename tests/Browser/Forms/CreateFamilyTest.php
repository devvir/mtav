<?php

// Copilot - Pending review

use App\Models\Family;
use App\Models\User;

uses()->group('Browser.Forms');

beforeEach(function () {
    $this->actingAs(User::find(1));
    // Default to single-project context for tests that require it.
    setFirstProjectAsCurrent();

    config()->set('app.locale', 'en');
});

test('Create Family form is reachable and displays expected fields', function () {
    visit(route('families.create'))
        ->screenshot(filename: 'families-create-form')
        ->assertPresent('input[name="name"]')
        ->assertPresent('select[name="project_id[]"]')
        ->assertPresent('select[name="unit_type_id[]"]')
        ->assertNoSmoke();
});

test('Multi-project: form shows project select when no project selected', function () {
    resetCurrentProject();

    visit(route('families.create'))
        ->screenshot(filename: 'families-create-form-multiproject')
        ->assertPresent('select[name="project_id[]"]')
        ->assertNoSmoke();
});

test('Multi-project: cannot submit without selecting a project', function () {
    resetCurrentProject();

    $name = 'E2E Family No Project ' . uniqid();

    visit(route('families.create'))
        ->screenshot(filename: 'before:families-create-no-project')
        ->fill('input[name="name"]', $name)
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:families-create-no-project');

    expect(Family::where('name', $name))->not->toExist();
});

test('Can create a Family (happy path)', function () {
    $name = 'E2E Family ' . uniqid();

    visit(route('families.create'))
        ->screenshot(filename: 'before:families-create')
        ->fill('input[name="name"]', $name)
        ->select('select[name="project_id[]"]', '1')
        ->select('select[name="unit_type_id[]"]', '1')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:families-create');

    expect(Family::where('name', $name))->toExist();
});

test('Validation: name must be between 2 and 255 characters', function () {
    visit(route('families.create'))
        ->screenshot(filename: 'before:families-create-validation-name-length')
        ->fill('input[name="name"]', 'A')
        ->select('select[name="project_id[]"]', '1')
        ->select('select[name="unit_type_id[]"]', '1')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:families-create-validation-name-length')
        ->assertSee('The Name field must be between 2 and 255 characters');
});
