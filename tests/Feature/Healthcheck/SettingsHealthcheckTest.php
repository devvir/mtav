<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('visits Profile Settings', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('profile.edit', asMember: 102, redirects: false);

            expect($response->status())->toBe(200);
        });
    });

    describe('visits Password Settings', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('password.edit', asMember: 102, redirects: false);

            expect($response->status())->toBe(200);
        });
    });

    describe('visits Appearance Settings', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('appearance', asMember: 102, redirects: false);

            expect($response->status())->toBe(200);
        });
    });
});

describe('When an Admin', function () {
    describe('visits Profile Settings', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('profile.edit', asAdmin: 11, redirects: false);

            expect($response->status())->toBe(200);
        });
    });

    describe('visits Password Settings', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('password.edit', asAdmin: 11, redirects: false);

            expect($response->status())->toBe(200);
        });
    });

    describe('visits Appearance Settings', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('appearance', asAdmin: 11, redirects: false);

            expect($response->status())->toBe(200);
        });
    });
});
