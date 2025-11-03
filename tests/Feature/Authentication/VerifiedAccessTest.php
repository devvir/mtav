<?php

/**
 * Tests for authentication requirements on protected routes.
 *
 * Verifies that the `auth` middleware properly restricts access to
 * authenticated users only. Guests should be redirected to the login page.
 */

uses()->group('Feature.Authentication');

describe('When visiting an authorized-only page', function () {
    describe('as a Guest', function () {
        it('it redirects to the Login page', function () {
            $response = $this->visitRoute('members.index', redirects: false);

            expect($response)->toRedirectTo('login');
        });
    });

    describe('as an authorized User', function () {
        it('should be allowed in', function () {
            $response = $this->visitRoute('members.index', asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });
});
