<?php

/**
 * Tests for the invitation confirmation form display and authentication.
 *
 * An "invited user" is authenticated but has not yet completed registration
 * (invitation_accepted_at = null). This file tests the form display and
 * authentication flow when accessing the invitation confirmation page.
 *
 * Note: Redirect behavior for invited users is tested in InvitedUserRedirectsTest.php
 *
 * Covers:
 * - Authentication flow when visiting the invitation page with email+token
 * - Form display and pre-filled data for different user types
 * - Security: preventing access to other users' invitations
 * - Already-confirmed users trying to access the invitation page
 */

use Illuminate\Support\Facades\Auth;

uses()->group('Feature.Invitation');

describe('When visiting the invitation confirmation page with email+token', function () {
    it('logs out any currently authenticated user before authenticating the invited user', function () {
        // Visit invitation page as a fully registered Member with email+token in URL
        $this->visitRoute('invitation.edit', asMember: 102, data: [
            'email' => 'invited148@example.com',
            'token' => 'randomtoken',
        ]);

        // Should now be authenticated as the invited user (#148), not Member 102
        expect(Auth::id())->toBe(148);
    });

    it('authenticates the invited user', function () {
        $this->visitRoute('invitation.edit', data: [
            'email' => 'invited148@example.com',
            'token' => 'randomtoken',
        ]);

        expect(Auth::id())->toBe(148);
    });
});

describe('When visiting the invitation confirmation page', function () {
    describe('as a Guest without an invitation', function () {
        it('redirects to the login page', function () {
            $response = $this->visitRoute('invitation.edit');

            expect(inertiaRoute($response))->toBe('login');
        });
    });

    describe('as an already confirmed User', function () {
        it('redirects to the the Login page', function () {
            // Member 102 is already confirmed (invitation_accepted_at is not null)
            $response = $this->visitRoute('invitation.edit', asMember: 102, redirects: false);

            expect($response)->toRedirectTo('login');
        });
    });

    describe('as a different invited User (wrong token)', function () {
        it('redirects to the login page with an error', function () {
            $response = $this->visitRoute('invitation.edit', data: [
                'email' => 'invited148@example.com',
                'token' => 'wrong-token',
            ]);

            expect(inertiaRoute($response))->toBe('login');
        });
    });

    describe('as an invited Admin', function () {
        it('shows the invitation confirmation form', function () {
            $response = $this->visitRoute('invitation.edit', asAdmin: 18);

            expect(inertiaRoute($response))->toBe('invitation.edit');
        });

        it('the page props include their email and projects', function () {
            $response = $this->visitRoute('invitation.edit', asAdmin: 18);
            $user = $response->inertiaProps('user');

            expect($user['is_admin'])->toBeTrue()
                ->and($user['email'])->toBe('invited18@example.com')
                ->and($user['projects'])->not->toBeEmpty();
        });

        it('does not provide family information', function () {
            $response = $this->visitRoute('invitation.edit', asAdmin: 18);

            expect($response->inertiaProps('family'))->toBeEmpty();
        });
    });

    describe('as an invited Member', function () {
        it('shows the invitation confirmation form', function () {
            $response = $this->visitRoute('invitation.edit', asMember: 148);

            expect(inertiaRoute($response))->toBe('invitation.edit');
        });

        it('provides user data with email', function () {
            $response = $this->visitRoute('invitation.edit', asMember: 148);
            $user = $response->inertiaProps('user');

            expect($user['email'])->toBe('invited148@example.com')
                ->and($user['is_admin'])->toBeFalse();
        });

        it('provides family information', function () {
            $response = $this->visitRoute('invitation.edit', asMember: 148);
            $family = $response->inertiaProps('family');

            expect($family)->not->toBeNull();
        });
    });
});
