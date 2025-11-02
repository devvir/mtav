<?php

uses()->group('Feature.Healthcheck');

describe('When a Member', function () {
    describe('visits the Gallery', function () {
        it('loads the page', function () {
            $response = $this->visitRoute('gallery', asMember: 102, redirects: false);

            expect($response)->toBeOk();
        });
    });
});

describe('When an Admin', function () {
    describe('visits the Gallery', function () {
        it('loads the page if they manage only one project', function () {
            // Admin #11 manages only Project #1, so it's auto-selected and they see the gallery
            $response = $this->visitRoute('gallery', asAdmin: 11, redirects: false);

            expect($response)->toBeOk();
        });

        it('redirects to Projects if no Project is selected', function () {
            // Admin #12 manages Projects #2 and #3, so they must select one first
            $response = $this->visitRoute('gallery', asAdmin: 12, redirects: false);

            expect($response)->toRedirectTo('projects.index');
        });
    });
});

