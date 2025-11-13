<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('visits the Admin Listing', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('admins.index', asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits an Admin Profile', function () {
        it('loads the page', function () {
            // Member #102 is in Project #1, Admin #11 manages Project #1
            $response = $this->visitRoute(['admins.show', 11], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });
});

describe('When an Admin', function () {
    describe('visits the Admin Listing', function () {
        it('loads the page if a project is not selected', function () {
            // Admin #12 manages Projects #2 and #3
            $response = $this->visitRoute('admins.index', asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });

        it('loads the page if a project is selected', function () {
            setCurrentProject(2);

            $response = $this->visitRoute('admins.index', asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits an Admin Profile', function () {
        it('loads the page if a project is not selected', function () {
            // Admin #12 and Admin #13 both manage Project #3
            $response = $this->visitRoute(['admins.show', 13], asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });

        it('loads the page if a project is selected', function () {
            setCurrentProject(3);

            // Admin #12 and Admin #13 both manage Project #3
            $response = $this->visitRoute(['admins.show', 13], asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('opens the "New Admin" form', function () {
        it('loads the form if a project is not selected', function () {
            // Admin #12 manages Projects #2 and #3 (multi-project admin)
            $response = $this->visitRoute('admins.create', asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });

        it('loads the form if a project is selected', function () {
            setCurrentProject(2);

            $response = $this->visitRoute('admins.create', asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('opens the "Edit Admin" form', function () {
        it('loads their own form', function () {
            $response = $this->visitRoute(['admins.edit', 12], asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });
    });
});
