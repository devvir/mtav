<?php

// Copilot - Pending review

use App\Models\Unit;
use App\Models\User;

uses()->group('Browser.Forms');

beforeEach(function () {
    $this->actingAs(User::find(1));
    // These require a selected project context
    setFirstProjectAsCurrent();

    config()->set('app.locale', 'en');
});

test('Create Unit form is reachable and displays expected fields', function () {
    visit(route('units.create'))
        ->screenshot(filename: 'units-create-form')
        ->assertPresent('select[name="unit_type_id[]"]')
        ->assertPresent('input[name="identifier"]')
        ->assertNoSmoke();
});

test('Can create a Unit (happy path)', function () {
    $identifier = 'E2E Unit ' . uniqid();

    visit(route('units.create'))
        ->screenshot(filename: 'before:units-create')
        ->select('select[name="unit_type_id[]"]', '2')
        ->fill('input[name="identifier"]', $identifier)
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:units-create');

    expect(Unit::where('identifier', $identifier))->toExist();
});

test('Validation: identifier max length', function () {
    $long = str_repeat('a', 300);

    visit(route('units.create'))
        ->screenshot(filename: 'before:units-create-validation-identifier-length')
        ->select('select[name="unit_type_id[]"]', '2')
        ->fill('input[name="identifier"]', $long)
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:units-create-validation-identifier-length')
        ->assertSee('The Identifier field must be between 2 and 255 characters');
});
