<?php

/**
 * Tests for the forced-logout behavior of the HandleSelectedProject middleware.
 *
 * A non-superadmin User with no active Projects (deleted project, deactivated
 * membership, etc.) is logged out and redirected to the Login page.
 */

uses()->group('Feature.Authentication');

describe('When a User has no active Projects', function () {
    it('logs out a Member whose project membership is inactive', function () {
        // Member #100 is inactive in project_user (Family #2, Project #1)
        $response = $this->visitRoute('dashboard', asMember: 100, redirects: false);

        expect($response)->toRedirectTo('login');
        $this->assertGuest();
    });

    it('logs out an Admin with no Projects assigned', function () {
        // Admin #10 manages no Projects
        $response = $this->visitRoute('dashboard', asAdmin: 10, redirects: false);

        expect($response)->toRedirectTo('login');
        $this->assertGuest();
    });
});

describe('When a User has at least one active Project', function () {
    it('keeps an active Member logged in', function () {
        $response = $this->visitRoute('dashboard', asMember: 102, redirects: false);

        expect($response)->toBeOk();
        $this->assertAuthenticated();
    });
});
