<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('visits the Family Listing', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('families.index', asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits their own Family Profile', function () {
        it('loads the page', function () {
            // Member #102 is in Family #4
            $response = $this->visitRoute(['families.show', 4], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits another Family Profile', function () {
        it('loads the page', function () {
            // Member #102 is in Family #4, visiting Family #5 (same project)
            $response = $this->visitRoute(['families.show', 5], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('opens their own Family\'s "Edit Family" form', function () {
        it('opens the form', function () {
            // Member #102 is in Family #4
            $response = $this->visitRoute(['families.edit', 4], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('tries to open another Family\'s "Edit Family" form', function () {
        it('denies access (Members can only edit their own Family)', function () {
            // Member #102 is in Family #4, trying to edit Family #5
            $response = $this->visitRoute(['families.edit', 5], asMember: 102, redirects: false);

            expect($response)->toBeUnauthorized();
        });
    });

    describe('tries to open the "New Family" form', function () {
        it('denies access (Members cannot create Families)', function () {
            $response = $this->visitRoute('families.create', asMember: 102, redirects: false);

            expect($response)->toBeUnauthorized();
        });
    });
});

describe('When an Admin', function () {
    /**
     * - if a project is selected, the Listing only shows Families from that project
     * - otherwise, it lists all Families from all the projects that the Admin manages
     */
    describe('visits the Family Listing', function () {
        it('loads the page for an Admin with: :dataset', function ($adminId) {
            $response = $this->visitRoute('families.index', asAdmin: $adminId, redirects: false);

            expect($response)->toBeOk();
        })->with(['project selected' => 11, 'no project selected' => 12]);
    });

    describe('visits a Family Profile', function () {
        it('loads the page if the Family is in one of their managed Projects', function () {
            // Admin #11 manages only Project #1 (auto-selected), Family #4 is in Project #1
            $response = $this->visitRoute(['families.show', 4], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if the Family is NOT in one of their managed Projects', function () {
            // Admin #11 manages only Project #1, Family #13 is in Project #2
            $response = $this->visitRoute(['families.show', 13], asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });
    });

    describe('opens the "New Family" form', function () {
        it('opens the form', function () {
            // Admin #11 manages only Project #1, so it's auto-selected
            $response = $this->visitRoute('families.create', asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('opens the "Edit Family" form', function () {
        it('opens the form if the Family is in one of their managed Projects', function () {
            // Admin #11 manages only Project #1 (auto-selected), Family #4 is in Project #1
            $response = $this->visitRoute(['families.edit', 4], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if the Family is NOT in one of their managed Projects', function () {
            // Admin #11 manages only Project #1, Family #13 is in Project #2
            $response = $this->visitRoute(['families.edit', 13], asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });
    });
});
