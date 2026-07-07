<?php

/**
 * Health check for the Contact page (ContactController).
 */

uses()->group('Feature.Healthcheck');

describe('Contact page', function () {
    it('loads for a Member contacting an Admin of their Project', function () {
        // Member #102 (Project #1), Admin #11 manages Project #1
        $response = $this->visitRoute(['contact', 11], asMember: 102, redirects: false);

        expect($response)->toBeOk()->toUsePage('Admins/Contact');
    });

    it('is not found for an Admin outside the Member\'s Project', function () {
        // Admin #12 manages Projects #2/#3 — invisible to Member #102 (Project #1)
        $response = $this->visitRoute(['contact', 12], asMember: 102, redirects: false);

        expect($response)->toBeNotFound();
    });

    it('redirects guests to the login page', function () {
        $response = $this->visitRoute(['contact', 11], redirects: false);

        expect($response)->toRedirectTo('login');
    });
});
