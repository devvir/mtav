<?php

/**
 * Tests for switching the Current Project (CurrentProjectController).
 */

use App\Models\User;

uses()->group('Feature.Projects');

describe('Setting the Current Project', function () {
    it('sets a Project the Admin manages and redirects to the Dashboard', function () {
        $this->actingAs(User::find(12)); // Admin #12 manages Projects #2, #3

        $response = $this->post(route('setCurrentProject', 2));

        expect(currentProjectId())->toBe(2);
        $response->assertRedirect(route('dashboard'));
    });

    it('cannot set a Project the Admin does not manage (project scope hides it)', function () {
        $this->actingAs(User::find(12)); // Admin #12 does NOT manage Project #1

        $response = $this->post(route('setCurrentProject', 1));

        $response->assertNotFound();
        expect(currentProjectId())->toBeNull();
    });

    it('allows a Superadmin to set any Project', function () {
        $this->actingAs(User::find(1));

        $response = $this->post(route('setCurrentProject', 2));

        expect(currentProjectId())->toBe(2);
        $response->assertRedirect(route('dashboard'));
    });
});

describe('Unsetting the Current Project', function () {
    it('unsets the Current Project and redirects to the Projects index', function () {
        $this->actingAs(User::find(12)); // Admin #12 manages Projects #2, #3
        setCurrentProject(2);

        $response = $this->delete(route('resetCurrentProject'));

        expect(currentProjectId())->toBeNull();
        $response->assertRedirect(route('projects.index'));
    });
});
