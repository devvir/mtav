<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('tries to visit the Project Listing', function () {
        it('redirects to Home', function () {
            $response = $this->visitRoute('projects.index', asMember: 102, redirects: false);

            expect($response)->toBeUnauthorized();
        });
    });

    describe('visits a Project Profile', function () {
        it('denies access', function () {
            $response = $this->visitRoute(['projects.show', 1], asMember: 102, redirects: false);

            expect($response)->toBeUnauthorized();
        });
    });

    describe('tries to open the "New Project" form', function () {
        it('shows as Not Found', function () {
            $response = $this->visitRoute('projects.create', asMember: 102, redirects: false);

            expect($response)->toBeNotFound();
        });
    });

    describe('tries to open the "Edit Project" form', function () {
        it('redirects to Home', function () {
            $response = $this->visitRoute(['projects.edit', 1], asMember: 102, redirects: false);

            expect($response)->toBeUnauthorized();
        });
    });
});

describe('When an Admin', function () {
    describe('visits the Project Listing', function () {
        it('loads the page for an Admin managing 2+ projects', function () {
            // Admin #12 manages Projects #2 and #3
            $response = $this->visitRoute('projects.index', asAdmin: 12, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access for an Admin managing only one Project', function () {
            // Admin #11 manages only Project #1
            $response = $this->visitRoute('projects.index', asAdmin: 11, redirects: false);

            expect($response)->toBeUnauthorized();
        });
    });

    describe('visits a Project Profile', function () {
        it('loads the page if they manage that Project', function () {
            // Admin #11 manages only Project #1
            $response = $this->visitRoute(['projects.show', 1], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if they don\'t manage that Project', function () {
            // Admin #11 manages only Project #1, trying to view Project #2
            $response = $this->visitRoute(['projects.show', 2], asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });
    });

    describe('tries to open the "New Project" form', function () {
        it('shows as Not Found', function () {
            $response = $this->visitRoute('projects.create', asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });
    });

    describe('opens the "Edit Project" form', function () {
        it('opens the form if they manage that Project', function () {
            // Admin #11 manages only Project #1
            $response = $this->visitRoute(['projects.edit', 1], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if they don\'t manage that Project', function () {
            // Admin #11 manages only Project #1, trying to edit Project #2
            $response = $this->visitRoute(['projects.edit', 2], asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });
    });
});

describe('When a Superadmin', function () {
    describe('opens the "New Project" form', function () {
        it('opens the form', function () {
            $response = $this->visitRoute('projects.create', asAdmin: 1, redirects: false);

            expect($response)->toBeOk();
        });
    });
});
