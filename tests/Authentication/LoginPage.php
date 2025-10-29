<?php

/**
 * Tests for the login page behavior - guest access, authenticated user redirects,
 * and edge cases like deleted/inactive projects and users.
 */

use App\Models\User;

uses()->group('Authentication');

describe('When visiting the login page', function () {
    describe('as Guest', function () {
        it('renders the login page', function () {
            $response = $this->getRoute('login');

            expect($response->status())->toBe(200);
        });
    });

    describe('as Authenticated Member', function () {

        it('redirects them to the Dashboard (if their Project is active)', function () {
            $response = $this->visitsRoute('login', asUser: 7);

            expect(inertiaRoute($response))->toBe('home');
        });

        it('logs them out if they are not active in any Project', function () {
            // Member #5 has only one Project, and is inactive in it
            $response = $this->visitsRoute('login', asUser: 5);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('logs them out if their Project was deleted', function () {
            // Member #52 belongs to Project #5 (soft-deleted)
            $response = $this->visitsRoute('login', asUser: 52);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to Dashboard if their Project is inactive', function () {
            // Member #51 is an active member of Project #4, which is itself inactive
            $response = $this->visitsRoute('login', asUser: 51);

            expect(inertiaRoute($response))->toBe('home');
        });
    });

    describe('as Admin', function () {
        it('redirects to the Dashboard if they manage only one Project', function () {
            // Admin #2 manages only Project #1
            $response = $this->visitsRoute('login', asUser: 2);

            expect(inertiaRoute($response))->toBe('home');
        });

        it('redirects to projects index if they manage more than one Project', function () {
            // Admin #3 manages Projects #2 and #3
            $response = $this->visitsRoute('login', asUser: 3);

            expect(inertiaRoute($response))->toBe('projects.index');
        });

        it('logs out and redirect back to login if they manage no projects', function () {
            // Admin #1 has no project assignments
            $response = $this->visitsRoute('login', asUser: 1);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to the Dashboard if current project is set and managed by them', function () {
            // Admin #4 manages Projects #2 (inactive), #3, #4
            setCurrentProject(3);

            $response = $this->visitsRoute('login', asUser: 4);

            expect(inertiaRoute($response))->toBe('home');
        });

        it('redirects to Projects and resets current project if admin does not manage selected one', function () {
            // Admin #3 manages Projects #2 and #3, but NOT #1
            setCurrentProject(1);

            $response = $this->visitsRoute('login', asUser: 3);

            expect(currentProjectId())->toBeNull();
            expect(inertiaRoute($response))->toBe('projects.index');
        });

        it('redirects back to login if admin manages only a deleted project', function () {
            // Admin #50 manages only Project #5 (soft-deleted)
            $response = $this->visitsRoute('login', asUser: 50);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to home with remaining project selected when current one is deleted and admin manages only 1 other', function () {
            // Admin #53 manages Projects #2 (active) and #5 (deleted)
            setCurrentProject(5, withTrashed: true);

            $response = $this->visitsRoute('login', asUser: 53);

            expect(currentProjectId())->toBe(2);
            expect(inertiaRoute($response))->toBe('home');
        });

        it('redirects to home, with no project selected when current one is deleted and admin manages at least 2 others', function () {
            // Admin #54 manages Projects #2, #3, #4 (active) and #5 (deleted)
            setCurrentProject(5, withTrashed: true);

            $response = $this->visitsRoute('login', asUser: 54);

            expect(currentProject())->toBeNull();
            expect(inertiaRoute($response))->toBe('projects.index');
        });
    });
});
