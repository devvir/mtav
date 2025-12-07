<?php

// Copilot - Pending review

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Project;
use App\Models\User;

uses()->group('Browser.Forms');

beforeEach(function () {
    $this->actingAs(User::find(1));

    // Events require a selected project context
    setFirstProjectAsCurrent();

    config()->set('app.locale', 'en');
});

test('Create Event form is reachable and displays expected fields', function () {
    visit(route('events.create'))
        ->screenshot(filename: 'events-create-form')
        ->assertPresent('select[name="project_id[]"]')
        ->assertPresent('select[name="type[]"]')
        ->assertPresent('input[name="title"]')
        ->assertPresent('input[name="description"]')
        ->assertPresent('input[name="location"]')
        ->assertPresent('input[name="start_date"]')
        ->assertPresent('input[name="end_date"]')
        ->assertNoSmoke();
});

test('Can create an Event (happy path)', function () {
    $title = 'E2E Event ' . uniqid();

    visit(route('events.create'))
        ->screenshot(filename: 'before:events-create')
        ->select('select[name="project_id[]"]', '1')
        ->select('select[name="type[]"]', EventType::ONLINE->value)
        ->fill('input[name="title"]', $title)
        ->fill('input[name="description"]', 'A valid event description for e2e')
        ->fill('input[name="location"]', 'https://example.com')
        ->fill('input[name="start_date"]', '2025-12-10T00:00')
        ->fill('input[name="end_date"]', '2025-12-11T00:00')
        ->select('select[name="is_published[]"]', '1')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:events-create');

    expect(Event::where('title', $title))->toExist();
});

test('Validation: end_date must be after start_date', function () {
    visit(route('events.create'))
        ->screenshot(filename: 'before:events-create-validation-dates')
        ->select('select[name="project_id[]"]', '1')
        ->select('select[name="type[]"]', EventType::ONLINE->value)
        ->fill('input[name="title"]', 'E2E Bad Dates ' . uniqid())
        ->fill('input[name="description"]', 'desc')
        ->fill('input[name="start_date"]', '2025-12-10T00:00')
        ->fill('input[name="end_date"]', '2025-12-01T00:00')
        ->click('button[type="submit"]')
        ->screenshot(filename: 'after:events-create-validation-dates')
        ->assertSee('The End Date field must be a date after Start Date');
});
