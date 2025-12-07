<?php

// Copilot - Pending review

use App\Models\User;

uses()->group('Browser.Healthcheck');

beforeEach(function () {
    $this->actingAs(User::find(1));
    // Ensure a single-project context for deterministic rendering
    setFirstProjectAsCurrent();
    config()->set('app.locale', 'en');
});

test('projects index visual snapshot', function () {
    visit(route('projects.index'))
        ->screenshot(filename: 'before:health-projects-index')
        ->assertScreenshotMatches();
});
