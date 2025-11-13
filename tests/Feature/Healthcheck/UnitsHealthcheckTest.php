<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('visits the Unit Listing', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('units.index', asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits Unit Details', function () {
        it('loads the page if the Unit is in their Project', function () {
            // Member #102 is in Project #1, Unit #1 is in Project #1
            $response = $this->visitRoute(['units.show', 1], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if the Unit is from another Project', function () {
            // Member #102 is in Project #1, Unit #4 is in Project #2
            $response = $this->visitRoute(['units.show', 4], asMember: 102, redirects: false);

            expect($response)->toBeNotFound();
        });
    });
});

describe('When an Admin', function () {
    describe('visits the Unit Listing', function () {
        it('loads the page if they manage only one Project', function () {
            // Admin #11 manages only Project #1, so it's auto-selected
            $response = $this->visitRoute('units.index', asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('redirects to Projects if no Project is selected', function () {
            // Admin #12 manages Projects #2 and #3, so they must select one first
            $response = $this->visitRoute('units.index', asAdmin: 12, redirects: false);

            expect($response)->toRedirectTo('projects.index');
        });
    });

    describe('visits Unit Details', function () {
        it('loads the page if the Unit is in their managed Project', function () {
            // Admin #11 manages only Project #1 (auto-selected), Unit #1 is in Project #1
            $response = $this->visitRoute(['units.show', 1], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if the Unit is from another Project', function () {
            // Admin #11 manages only Project #1, Unit #4 is in Project #2
            $response = $this->visitRoute(['units.show', 4], asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });

        it('redirects to Projects if no Project is selected', function () {
            // Unit #1 exists, but a Project must be selected to access Unit Detail pages
            $response = $this->visitRoute(['units.show', 1], asAdmin: 12, redirects: false);

            expect($response)->toBeNotFound();
        });
    });

    describe('opens the "New Unit" form', function () {
        it('loads the form if they manage that Project', function () {
            // Admin #11 manages only Project #1, so it's auto-selected
            $response = $this->visitRoute('units.create', asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('redirects to Projects if no Project is selected', function () {
            // Admin #12 manages Projects #2 and #3, so they must select one first
            $response = $this->visitRoute('units.create', asAdmin: 12, redirects: false);

            expect($response)->toRedirectTo('projects.index');
        });
    });

    describe('opens the "Edit Unit" form', function () {
        it('loads the page if they manage only one Project', function () {
            // Admin #11 manages only Project #1 (auto-selected), Unit #1 is in Project #1
            $response = $this->visitRoute(['units.edit', 1], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('redirects to Projects if no Project is selected', function () {
            // Admin #12 manages Projects #2 and #3, so they must select one first
            $response = $this->visitRoute(['units.edit', 1], asAdmin: 12, redirects: false);

            expect($response)->toBeNotFound();
        });
    });
});
