<?php

uses()->group('Feature.Healthcheck');

describe('When a Member visits the Dashboard', function () {
    it('loads the page', function () {
        $response = $this->visitRoute('home', asMember: 102, redirects: false);

        expect($response->status())->toBe(200);
    });
});

describe('When an Admin visits the Dashboard', function () {
    it('loads the page if they manage only one project', function () {
        // Admin #11 manages only Project #1, so it's auto-selected and they see the dashboard
        $response = $this->visitRoute('home', asAdmin: 11, redirects: false);

        expect($response->status())->toBe(200);
    });

    it('redirects to Projects if no Project is selected', function () {
        // Admin #12 manages Projects #2 and #3, so they must select one first
        $response = $this->visitRoute('home', asAdmin: 12, redirects: false);

        expect($response)->toRedirectTo('projects.index');
    });
});
