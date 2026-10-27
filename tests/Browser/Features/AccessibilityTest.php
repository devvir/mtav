<?php

/**
 * The main pages pass automated accessibility checks (axe): labels, roles, contrast…
 */

it('has accessible pages for people not signed in', function () {
    visit(route('login'))->assertNoAccessibilityIssues();
    visit(route('register'))->assertNoAccessibilityIssues();
});

it('has an accessible member app', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type);
    $this->as(aMemberOf(aFamily($project, $type)));

    foreach (['projects.show', 'lottery.show', 'events.index', 'media.index', 'families.index'] as $route) {
        visit(route($route, $project))->assertNoAccessibilityIssues();
    }
});

it('has an accessible admin area', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    foreach (['manage.overview', 'manage.units.create', 'manage.invitations.index', 'manage.lottery.show'] as $route) {
        visit(route($route, $project))->assertNoAccessibilityIssues();
    }
});
