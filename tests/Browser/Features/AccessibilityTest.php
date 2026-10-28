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

    foreach (['manage.overview', 'units.index', 'manage.invitations.index', 'lottery.show'] as $route) {
        visit(route($route, $project))->assertNoAccessibilityIssues();
    }
});

/**
 * Every component of the design system, in each of its states (the UI Lab holds hover,
 * pressed and focus still), passes in every palette, scheme and contrast.
 */
it('has accessible components in every palette, scheme and contrast', function (string $palette, string $scheme, string $contrast) {
    $this->as(aSuperadmin());

    $page = visit(route('admin.lab'));
    $page->script("() => {
        document.cookie = 'palette={$palette};path=/';
        document.cookie = 'scheme={$scheme};path=/';
        document.cookie = 'contrast={$contrast};path=/';
    }");
    $page->refresh();

    // The appearance asked for is the one drawn
    $brand = ['terracotta' => '#ea621f', 'indigo' => '#5062f8', 'forest' => '#33c68a', 'ocean' => '#15aabf'][$palette];
    $page->assertScript("document.documentElement.getAttribute('data-mantine-color-scheme')", $scheme)
        ->assertScript("document.documentElement.hasAttribute('data-high-contrast')", $contrast === 'high')
        ->assertScript("getComputedStyle(document.documentElement).getPropertyValue('--mantine-color-brand-6').trim()", $brand);

    foreach (['Foundations', 'Controls', 'Feedback', 'Surfaces', 'People', 'Navigation'] as $section) {
        $page->click(".lab-nav button:has-text(\"{$section}\")")->assertNoAccessibilityIssues();
    }
})->with(['terracotta', 'indigo', 'forest', 'ocean'])->with(['light', 'dark'])->with(['standard', 'high']);
