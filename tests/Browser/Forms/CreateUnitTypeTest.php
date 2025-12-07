<?php

// Copilot - Pending review

use App\Models\UnitType;
use App\Models\User;

uses()->group('Browser.Forms');

beforeEach(function () {
    $this->actingAs(User::find(1));
    // These require a selected project context
    setFirstProjectAsCurrent();

    config()->set('app.locale', 'en');
});

test('Create UnitType form is reachable and displays expected fields', function () {
    visit(route('unit_types.create'))
        ->screenshot(filename: 'unit_types-create-form')
        ->assertPresent('input[name="name"]')
        ->assertPresent('input[name="description"]')
        ->assertNoSmoke();
});

test('Can create a UnitType (happy path)', function () {
    $name = 'E2E UnitType ' . uniqid();

    visit(route('unit_types.create'))
        ->screenshot(filename: 'before:unit_types-create')
        ->fill('input[name="name"]', $name)
        ->fill('input[name="description"]', 'A valid description for e2e')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:unit_types-create');

    expect(UnitType::where('name', $name))->toExist();
});

test('Validation: description must be between 2 and 255 characters', function () {
    visit(route('unit_types.create'))
        ->screenshot(filename: 'before:unit_types-create-validation-desc')
        ->fill('input[name="name"]', 'E2E Name')
        ->fill('input[name="description"]', 'x')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:unit_types-create-validation-desc')
        ->assertSee('The Description field must be between 2 and 255 characters');
});
