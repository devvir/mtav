<?php

/**
 * Test the behavior of the app when visiting the login page - as guest, authenticated
 * Members or Admin. Consider scenarios like deleted/inactive Projects and inactive Members.
 */
uses()->group('Feature.Authentication');

describe('When visiting the login page', function () {
    describe('as Guest', function () {
        it('renders the login page', function () {
            $response = $this->visitRoute('login', redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('as Authenticated Member', function () {

        it('redirects them to the Dashboard (if their Project is active)', function () {
            $response = $this->visitRoute('login', asUser: 102);

            expect(inertiaRoute($response))->toBe('dashboard');
        });

        it('logs them out if they are not active in any Project', function () {
            // Member #100 has only one Project, and is inactive in it
            $response = $this->visitRoute('login', asUser: 100);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('logs them out if their Project was deleted', function () {
            // Member #146 belongs to Project #5 (soft-deleted)
            $response = $this->visitRoute('login', asUser: 146);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to Dashboard if their Project is inactive', function () {
            // Member #145 is an active Member of Project #4, which is itself inactive
            $response = $this->visitRoute('login', asUser: 145);

            expect(inertiaRoute($response))->toBe('dashboard');
        });
    });

    describe('as Admin', function () {
        it('redirects to the Dashboard if they manage only one Project', function () {
            // Admin #11 manages only Project #1
            $response = $this->visitRoute('login', asUser: 11);

            expect(inertiaRoute($response))->toBe('dashboard');
        });

        it('redirects to Projects index if they manage more than one Project', function () {
            // Admin #12 manages Projects #2 and #3
            $response = $this->visitRoute('login', asUser: 12);

            expect(inertiaRoute($response))->toBe('projects.index');
        });

        it('logs them out and redirects back to login page if they manage no Projects', function () {
            // Admin #10 has no Project assignments
            $response = $this->visitRoute('login', asUser: 10);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to the Dashboard if current Project is set and they manage it', function () {
            // Admin #13 manages Projects #2 (inactive), #3, #4
            setCurrentProject(3);

            $response = $this->visitRoute('login', asUser: 13);

            expect(inertiaRoute($response))->toBe('dashboard');
        });

        it('redirects to Projects index and resets current Project if they do not manage the selected one', function () {
            // Admin #12 manages Projects #2 and #3, but NOT #1
            setCurrentProject(1);

            $response = $this->visitRoute('login', asUser: 12);

            expect(currentProjectId())->toBeNull();
            expect(inertiaRoute($response))->toBe('projects.index');
        });

        it('redirects back to login if they manage only a deleted Project', function () {
            // Admin #14 manages only Project #5 (soft-deleted)
            $response = $this->visitRoute('login', asUser: 14);

            expect(inertiaRoute($response))->toBe('login');
        });

        it('redirects to Dashboard with remaining Project selected when current one is deleted and they manage only 1 other', function () {
            // Admin #15 manages Projects #2 (active) and #5 (deleted)
            setCurrentProject(5, withTrashed: true);

            $response = $this->visitRoute('login', asUser: 15);

            expect(currentProjectId())->toBe(2);
            expect(inertiaRoute($response))->toBe('dashboard');
        });

        it('redirects to Projects index (with none selected) when current Project is deleted and they manage 2+ others', function () {
            // Admin #16 manages Projects #2, #3, #4 (active) and #5 (deleted)
            setCurrentProject(5, withTrashed: true);

            $response = $this->visitRoute('login', asUser: 16);

            expect(currentProjectId())->toBeNull();
            expect(inertiaRoute($response))->toBe('projects.index');
        });
    });
});
