<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('visits the Member Listing', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('members.index', asMember: 102, redirects: false);

            expect($response->status())->toBe(200);
        });
    });

    describe('visits their own Profile', function () {
        it('loads the page', function () {
            $response = $this->visitRoute(['members.show', 102], asMember: 102, redirects: false);

            expect($response->status())->toBe(200);
        });
    });

    describe('visits another Member\'s Profile', function () {
        it('loads the page', function () {
            $response = $this->visitRoute(['members.show', 105], asMember: 102, redirects: false);

            expect($response->status())->toBe(200);
        });
    });

    describe('tries to open their own "Edit Member" form', function () {
        it('redirects to the Dashboard (should use Settings > Profile)', function () {
            $response = $this->visitRoute(['members.edit', 102], asMember: 102, redirects: false);

            expect($response)->toRedirectTo('home');
        });
    });

    describe('tries to open another Member\'s "Edit Member" form', function () {
        it('redirects to the Dashboard (Members cannot edit other Members)', function () {
            $response = $this->visitRoute(['members.edit', 105], asMember: 102, redirects: false);

            expect($response)->toRedirectTo('home');
        });
    });

    describe('opens the "Invite Family Member" form', function () {
        it('opens the form', function () {
            $response = $this->visitRoute('members.create', asMember: 102, redirects: false);

            expect($response->status())->toBe(200);
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

            expect($response->status())->toBe(200);
        })->with(['project selected' => 11, 'no project selected' => 12]);
    });

    describe('visits a Member Profile', function () {
        it('loads the page if the Member is in one of their managed Projects', function () {
            // Admin #11 manages only Project #1 (auto-selected), Member #102 is in Project #1
            $response = $this->visitRoute(['members.show', 102], asAdmin: 11, redirects: false);

            expect($response->status())->toBe(200);
        });

        it('denies access if the Member is NOT in one of their managed Projects', function () {
            // Admin #11 manages only Project #1, Member #136 is in Project #2
            $response = $this->visitRoute(['members.show', 136], asAdmin: 11, redirects: false);

            expect($response->status())->toBe(403);
        });
    });

    describe('opens the "New Member" form', function () {
        it('opens the form', function () {
            // Admin #11 manages only Project #1, so it's auto-selected
            $response = $this->visitRoute('members.create', asAdmin: 11, redirects: false);

            expect($response->status())->toBe(200);
        });
    });

    describe('opens the "Edit Member" form', function () {
        it('opens the form if the Member is in one of their managed Projects', function () {
            // Admin #11 manages only Project #1 (auto-selected), Member #102 is in Project #1
            $response = $this->visitRoute(['members.edit', 102], asAdmin: 11, redirects: false);

            expect($response->status())->toBe(200);
        });

        it('denies access if the Member is NOT in one of their managed Projects', function () {
            // Admin #11 manages only Project #1, Member #136 is in Project #2
            $response = $this->visitRoute(['members.edit', 136], asAdmin: 11, redirects: false);

            expect($response->status())->toBe(403);
        });
    });
});
