<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('visits the Member Listing', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('members.index', asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits their own Profile', function () {
        it('loads the page', function () {
            $response = $this->visitRoute(['members.show', 102], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('visits another Member\'s Profile', function () {
        it('loads the page', function () {
            $response = $this->visitRoute(['members.show', 105], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('tries to open their own "Edit Member" form', function () {
        it('opens the form', function () {
            $response = $this->visitRoute(['members.edit', 102], asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('tries to open another Member\'s "Edit Member" form', function () {
        it('denies access (Members cannot edit other Members)', function () {
            $response = $this->visitRoute(['members.edit', 105], asMember: 102, redirects: false);

            expect($response)->toRedirectTo('home');
        });
    });

    describe('opens the "Invite Family Member" form', function () {
        it('opens the form', function () {
            $response = $this->visitRoute('members.create', asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });
});

describe('When an Admin', function () {
    /**
     * - if a project is selected, the Listing only shows Members from that project
     * - otherwise, it lists all Members from all the projects that the Admin manages
     */
    describe('visits the Member Listing', function () {
        it('loads the page for an Admin with: :dataset', function ($adminId) {
            $response = $this->visitRoute('members.index', asAdmin: $adminId, redirects: false);

            expect($response)->toBeOk();
        })->with(['project selected' => 11, 'no project selected' => 12]);
    });

    describe('visits a Member Profile', function () {
        it('loads the page if the Member is in one of their managed Projects', function () {
            // Admin #11 manages only Project #1 (auto-selected), Member #102 is in Project #1
            $response = $this->visitRoute(['members.show', 102], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if the Member is NOT in one of their managed Projects', function () {
            // Admin #11 manages only Project #1, Member #136 is in Project #2
            $response = $this->visitRoute(['members.show', 136], asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });
    });

    describe('opens the "New Member" form', function () {
        it('opens the form', function () {
            // Admin #11 manages only Project #1, so it's auto-selected
            $response = $this->visitRoute('members.create', asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });
    });

    describe('opens the "Edit Member" form', function () {
        it('opens the form if the Member is in one of their managed Projects', function () {
            // Admin #11 manages only Project #1 (auto-selected), Member #102 is in Project #1
            $response = $this->visitRoute(['members.edit', 102], asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('denies access if the Member is NOT in one of their managed Projects', function () {
            // Admin #11 manages only Project #1, Member #136 is in Project #2
            $response = $this->visitRoute(['members.edit', 136], asAdmin: 11, redirects: false);

            expect($response)->toBeNotFound();
        });
    });
});
