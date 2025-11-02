<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('visits the Log Listing', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('logs.index', asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits a Log Entry', function () {
        it('loads the page if the Log is in their Project', function () {
            // Log #1 is in Project #1, Member #102 is in Project #1
            $response = $this->visitRoute(['logs.show', 1], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if the Log is NOT in their Project', function () {
            // Log #3 is in Project #2, Member #102 is in Project #1
            $response = $this->visitRoute(['logs.show', 3], asMember: 102, redirects: false);

            expect($response)->toBeNotFound();
        });
    });
});

describe('When an Admin', function () {
    describe('visits the Log Listing', function () {
        it('loads the page if they manage only one Project', function () {
            // Admin #11 manages only Project #1, so it's auto-selected
            $response = $this->visitRoute('logs.index', asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('loads the page if no Project is selected', function () {
            // Admin #12 manages Projects #2 and #3: they'll see Logs from both projects
            $response = $this->visitRoute('logs.index', asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits a Log Entry', function () {
        it('loads the page if the Log is in a Project they manage', function () {
            // Admin #11 manages only Project #1 (auto-selected), Log #2 is in Project #1
            $response = $this->visitRoute(['logs.show', 2], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if the Log is NOT in a Project they manage', function () {
            // Admin #11 manages only Project #1, Log #3 is in Project #2
            $response = $this->visitRoute(['logs.show', 3], asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });
    });
});
