<?php

use App\Services\InvitationService;
use Illuminate\Support\Facades\Notification;

/**
 * The main pages pass automated accessibility checks (axe): labels, roles, contrast…
 */

it('has accessible pages for people not signed in', function () {
    Notification::fake();
    $project = aProject();
    app(InvitationService::class)->invite($project, anAdminOf($project), ['email' => 'invited@example.com'], null, false);

    assertAccessible(visit(route('login')));
    assertAccessible(visit(route('register')));
    assertAccessible(visit(route('password.request')));
    assertAccessible(visit(route('password.reset', ['token' => 'a-token', 'email' => 'someone@example.com'])));
    assertAccessible(visit(invitationLinkFor('invited@example.com')));
});

it('has an accessible member app', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type);
    $this->as(aMemberOf(aFamily($project, $type)));

    foreach (['projects.show', 'lottery.show', 'events.index', 'media.index', 'families.index', 'people.index', 'forum.index', 'chat.index'] as $route) {
        assertAccessible(visit(route($route, $project)));
    }

    foreach (['notifications.index', 'profile.edit'] as $route) {
        assertAccessible(visit(route($route)));
    }
});

it('has an accessible admin area', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    foreach (['manage.overview', 'units.index', 'manage.invitations.index', 'lottery.show'] as $route) {
        assertAccessible(visit(route($route, $project)));
    }
});

it('has accessible dialogs, where most changes are made', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type);
    $family = aFamily($project, $type);
    $member = aMemberOf($family);
    $project->lottery->update(['start_date' => now()->subMinute()]);
    $this->as(anAdminOf($project));

    $opened = fn (object $page) => assertAccessible($page->assertPresent('[role="dialog"]'));

    $opened(visit(route('families.index', [$project, 'new' => 1])));
    $opened(visit(route('families.show', [$project, $family])));
    $opened(visit(route('units.index', $project))->click('Add units'));
    $opened(visit(route('people.show', [$project, $member])));
    $opened(visit(route('people.show', [$project, $member]))->click('Change role or family'));
    $opened(visit(route('media.index', $project))->click('Share files'));
    $opened(visit(route('lottery.show', $project))->click('Run the lottery'));
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
        // Every component in every state at once: the heaviest page there is
        assertAccessible($page->click(".lab-nav button:has-text(\"{$section}\")"), seconds: 30);
    }
})->with(['terracotta', 'indigo', 'forest', 'ocean'])->with(['light', 'dark'])->with(['standard', 'high']);

it('sizes everything from the text size each person picks, the browser\'s own by default', function () {
    $this->as(aUser());
    $root = fn ($page) => $page->script('() => getComputedStyle(document.documentElement).fontSize');

    $page = visit(route('profile.edit', ['section' => 'preferences']));
    expect($root($page))->toBe('16px');

    $page->click('button:has-text("Medium")');
    expect($root($page))->toBe('19.2px');

    $page->click('button:has-text("Large")');
    expect($root($page))->toBe('22.4px');

    // Kept on the device, there from the first paint
    expect($root($page->refresh()))->toBe('22.4px');

    $page->click('button:has-text("Small")');
    expect($root($page))->toBe('16px');
});
