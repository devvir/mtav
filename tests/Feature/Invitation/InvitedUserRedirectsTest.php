<?php

/**
 * Tests for the redirect behavior of invited (not yet confirmed) users.
 *
 * An invited user is authenticated but has invitation_accepted_at = null.
 * The EnsureInvitationAccepted middleware handles these redirects.
 *
 * Covers:
 * - Accessing the Invitation route with/without credentials
 * - Invited users visiting the Login page (should logout)
 * - Invited users visiting protected pages (should redirect to Invitation)
 */
uses()->group('Feature.Invitation');

describe('When visiting the Invitation route', function () {
    describe('with valid credentials in the URL', function () {
        it('logs in an invited Member', function () {
            $response = $this->visitRoute('invitation.edit', data: [
                'email' => 'invited148@example.com',
                'token' => 'randomtoken',
            ]);

            expect(inertiaRoute($response))->toBe('invitation.edit');
            $this->assertAuthenticated();
        });

        it('logs in an invited Admin', function () {
            $response = $this->visitRoute('invitation.edit', data: [
                'email' => 'invited18@example.com',
                'token' => 'randomtoken',
            ]);

            expect(inertiaRoute($response))->toBe('invitation.edit');
            $this->assertAuthenticated();
        });
    });

    describe('with invalid credentials in the URL', function () {
        it('redirects to the Login page with an error message', function () {
            $response = $this->visitRoute('invitation.edit', data: [
                'email' => 'invited148@example.com',
            ]);

            expect(inertiaRoute($response))->toBe('login');
        });
    });

    describe('without credentials in the URL', function () {
        describe('and an invited User is authenticated', function () {
            it('shows the Invitation confirmation form for an invited Member', function () {
                $response = $this->visitRoute('invitation.edit', asMember: 148);

                expect(inertiaRoute($response))->toBe('invitation.edit');
            });

            it('shows the Invitation confirmation form for an invited Admin', function () {
                $response = $this->visitRoute('invitation.edit', asAdmin: 18);

                expect(inertiaRoute($response))->toBe('invitation.edit');
            });
        });

        describe('and no User is authenticated', function () {
            it('redirects to the Login page with an error message', function () {
                $response = $this->visitRoute('invitation.edit');

                expect(inertiaRoute($response))->toBe('login');
            });
        });

        describe('and a confirmed User is authenticated', function () {
            it('redirects to the Dashboard with an error message', function () {
                $response = $this->visitRoute('invitation.edit', asAdmin: 11);

                expect(inertiaRoute($response))->toBe('dashboard');
            });
        });
    });
});

describe('When an invited User is authenticated', function () {
    describe('and they visit the Login page', function () {
        it('logs them out if they are an invited Member', function () {
            $response = $this->visitRoute('login', asMember: 148);

            expect(inertiaRoute($response))->toBe('login');
            $this->assertGuest();
        });

        it('logs them out if they are an invited Admin', function () {
            $response = $this->visitRoute('login', asAdmin: 18);

            expect(inertiaRoute($response))->toBe('login');
            $this->assertGuest();
        });
    });

    describe('and they visit the Dashboard', function () {
        it('redirects to Invitation route if they are an invited Member', function () {
            $response = $this->visitRoute('dashboard', asMember: 148);

            expect(inertiaRoute($response))->toBe('invitation.edit');
            $this->assertAuthenticated();
        });

        it('redirects to Invitation route if they are an invited Admin', function () {
            $response = $this->visitRoute('dashboard', asAdmin: 18);

            expect(inertiaRoute($response))->toBe('invitation.edit');
            $this->assertAuthenticated();
        });
    });

    describe('and they visit the Members Index', function () {
        it('redirects to Invitation route if they are an invited Member', function () {
            $response = $this->visitRoute('members.index', asMember: 148);

            expect(inertiaRoute($response))->toBe('invitation.edit');
            $this->assertAuthenticated();
        });

        it('redirects to Invitation route if they are an invited Admin', function () {
            $response = $this->visitRoute('members.index', asAdmin: 18);

            expect(inertiaRoute($response))->toBe('invitation.edit');
            $this->assertAuthenticated();
        });
    });

    describe('and they visit the Projects Index', function () {
        it('redirects to Invitation route if they are an invited Member', function () {
            $response = $this->visitRoute('projects.index', asMember: 149);

            expect(inertiaRoute($response))->toBe('invitation.edit');
            $this->assertAuthenticated();
        });

        it('redirects to Invitation route if they are an invited Admin', function () {
            $response = $this->visitRoute('projects.index', asAdmin: 19);

            expect(inertiaRoute($response))->toBe('invitation.edit');
            $this->assertAuthenticated();
        });
    });
});
