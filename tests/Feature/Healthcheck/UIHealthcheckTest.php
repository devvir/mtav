<?php

/**
 * UI Health Check Tests
 *
 * Quick tour of all GET endpoints to ensure no obvious errors (500, 404, etc).
 * Tests meaningful page content rather than just HTTP status codes.
 *
 * Purpose: Detect broken pages, not business logic issues.
 */
uses()->group('Feature.Healthcheck');

describe('For an Admin', function () {
    it('includes Project name when visiting the Dashboard', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('dashboard', asAdmin: 11);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Project 1');
    });

    it('loads the Details of a Family without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['families.show', 4], asAdmin: 11);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Family 4');
    });

    it('loads the Families Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('families.index', asAdmin: 11);

        expect($response)->toBeOk();
    });

    it('loads the Details of a Member without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['members.show', 102], asAdmin: 11);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Member 102');
    });

    it('loads the Members Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('members.index', asAdmin: 11);

        expect($response)->toBeOk();
    });

    it('loads the Details of an Admin without errors', function () {
        setCurrentProject(2);

        $response = $this->visitRoute(['admins.show', 12], asAdmin: 12);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Admin 12 (manages 2,3)');
    });

    it('loads the Admins Index without errors', function () {
        setCurrentProject(2);

        $response = $this->visitRoute('admins.index', asAdmin: 12);

        expect($response)->toBeOk();
    });

    it('loads the Details of a Project without errors', function () {
        setCurrentProject(2);

        $response = $this->visitRoute(['projects.show', 2], asAdmin: 12);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Project 2');
    });

    it('loads the Projects Index without errors', function () {
        $response = $this->visitRoute('projects.index', asAdmin: 12);

        expect($response)->toBeOk();
    });

    it('loads the Details of a Unit without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['units.show', 1], asAdmin: 11);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Unit 1, Type 2');
    });

    it('loads the Units Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('units.index', asAdmin: 11);

        expect($response)->toBeOk();
    });

    it('loads the Details of a Unit Type without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['unit_types.show', 1], asAdmin: 11);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Type 1');
    });

    it('loads the Unit Types Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('unit_types.index', asAdmin: 11);

        expect($response)->toBeOk();
    });

    it('loads the Gallery page without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('gallery', asAdmin: 11);

        expect($response)->toBeOk();
    });

    it('loads the Details of an Event without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['events.show', 1], asAdmin: 11);

        expect($response)->toBeOk();
        expect($response->content())->toContain(__('Lottery'));
    });

    it('loads the Events Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('events.index', asAdmin: 11);

        expect($response)->toBeOk();
    });

    it('displays content when visiting the FAQ page', function () {
        $response = $this->visitRoute('documentation.faq', asAdmin: 11);

        expect($response)->toBeOk();
    });

    it('displays content when visiting the Guide page', function () {
        $response = $this->visitRoute('documentation.guide', asAdmin: 11);

        expect($response)->toBeOk();
    });

});

describe('For a Member', function () {
    it('includes Project name when visiting the Dashboard', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('dashboard', asMember: 102);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Project 1');
    });

    it('loads the Details of a Family without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['families.show', 4], asMember: 102);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Family 4');
    });

    it('loads the Families Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('families.index', asMember: 102);

        expect($response)->toBeOk();
    });

    it('loads the Details of a Member without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['members.show', 102], asMember: 102);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Member 102');
    });

    it('loads the Members Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('members.index', asMember: 102);

        expect($response)->toBeOk();
    });

    it('loads the Details of an Admin without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['admins.show', 11], asMember: 102);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Admin 11 (manages 1)');
    });

    it('loads the Admins Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('admins.index', asMember: 102);

        expect($response)->toBeOk();
    });

    it('loads the Details of a Unit without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['units.show', 1], asMember: 102);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Unit 1, Type 2');
    });

    it('loads the Units Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('units.index', asMember: 102);

        expect($response)->toBeOk();
    });

    it('loads the Details of a Unit Type without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['unit_types.show', 1], asMember: 102);

        expect($response)->toBeOk();
        expect($response->content())->toContain('Type 1');
    });

    it('loads the Unit Types Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('unit_types.index', asMember: 102);

        expect($response)->toBeOk();
    });

    it('loads the Gallery page without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('media.index', asMember: 102);

        expect($response)->toBeOk();
    });

    it('loads the Details of an Event without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute(['events.show', 1], asMember: 102);

        expect($response)->toBeOk();
        expect($response->content())->toContain(__('Lottery'));
    });

    it('loads the Events Index without errors', function () {
        setCurrentProject(1);

        $response = $this->visitRoute('events.index', asMember: 102);

        expect($response)->toBeOk();
    });

    it('displays content when visiting the FAQ page', function () {
        $response = $this->visitRoute('documentation.faq', asMember: 102);

        expect($response)->toBeOk();
    });

    it('displays content when visiting the Guide page', function () {
        $response = $this->visitRoute('documentation.guide', asMember: 102);

        expect($response)->toBeOk();
    });
});
