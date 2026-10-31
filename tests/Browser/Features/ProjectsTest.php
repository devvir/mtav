<?php

use App\Models\Project;

/**
 * Projects: starting one, approval and its limits, settings, switching between them.
 */

it('starts a project with room for most cooperatives, and asks for more, never pushed to', function () {
    $person = aUser();
    $this->as($person);

    visit(route('projects.create'))
        ->assertSee('Room for the project')
        ->assertDontSee('superadmin')
        ->fill('name', 'Cooperativa Las Acacias')
        ->fill('description', 'Forty dwellings')
        ->click('Start the project')
        ->assertDontSee('awaiting approval')
        ->click('button[aria-expanded]:has-text("Limited")')
        ->assertSee('What the project has room for:')
        ->click(button('Ask for unlimited'))
        ->click(inDialog('Ask for unlimited'))
        ->assertSee('We asked the MTAV team to make your project unlimited.')
        ->assertSee('Unlimited asked for')
        ->assertNoSmoke();

    expect(Project::sole()->approval_requested_at)->not->toBeNull();
});

it('lets a superadmin approve a project, whose admins are told', function () {
    $project = anUnapprovedProject();
    $admin = $project->admins()->first();
    app(\App\Services\ProjectService::class)->requestApproval($project, $admin);

    $this->as(aSuperadmin());

    visit(route('admin.projects.index'))
        ->click('nav a:has-text("Awaiting approval")')
        ->assertSee($project->name)
        ->click(button('Approve'))
        ->click(inDialog('Approve'))
        ->assertSee('is approved: its limits are lifted.');

    expect($project->fresh()->isApproved())->toBeTrue();

    $this->as($admin);

    visit(route('notifications.index'))
        ->assertSee('Your project is unlimited')
        ->click('Your project is unlimited')
        ->assertSee($project->name);
});

it('edits the project, and deletes it only by typing its name', function () {
    $project = aProject(['name' => 'Cooperativa Las Acacias']);
    $this->as(anAdminOf($project));

    $page = visit(route('manage.settings.edit', $project))
        ->fill('description', 'Forty-two dwellings now')
        ->click('Save')
        ->assertSee('The project was saved.')
        ->click(button('Delete the project'))
        ->assertSee('Type Cooperativa Las Acacias to confirm');

    expect($page->script('document.querySelector(\'[role="dialog"] button[type="submit"]\').disabled'))->toBeTrue();

    $page->fill('[role="dialog"] input', 'Cooperativa Las Acacias')
        ->click(inDialog('Delete the project'))
        ->assertSee('The project was deleted.');

    expect(Project::find($project->id))->toBeNull();
});

it('switches between projects', function () {
    $first = aProject(['name' => 'Cooperativa Uno']);
    $second = aProject(['name' => 'Cooperativa Dos']);
    $admin = anAdminOf($first, $second);
    $this->as($admin);

    visit(route('projects.show', $first))
        ->click('header button:has-text("Cooperativa Uno")')
        ->click('[role="menu"] >> text=Cooperativa Dos')
        ->assertPathIs("/projects/{$second->id}")
        ->assertSee('Cooperativa Dos');
});

it('shows someone in only one project no list of them, and the way to find another', function () {
    $project = aProject(['name' => 'Cooperativa Uno']);
    $this->as(aMemberOf(aFamily($project)), $project);

    visit(route('projects.show', $project))
        ->assertSee('Cooperativa Uno')
        ->click('header .shell-switcher')
        ->assertSeeIn('[role="menu"]', 'Find a project')
        ->assertDontSeeIn('[role="menu"]', 'All your projects')
        ->keys('[role="menu"]', 'Escape')
        ->click('[aria-label="Your account"]')
        ->assertDontSeeIn('[role="menu"]', 'Your projects');

    // Their account pages keep the project's places at hand
    visit(route('profile.edit'))
        ->assertSeeIn('.shell-nav', 'Lottery')
        ->assertDontSeeIn('.shell-nav', 'Your projects');
});

it('gives admins the project\'s places, and its settings apart at the bottom, in one sidebar', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    visit(route('projects.show', $project))
        ->assertSeeIn('.shell-apart', 'Settings')
        ->click('.shell-apart >> text=Settings')
        ->assertPathIs(route('manage.overview', $project, absolute: false))
        ->click('.settings-tabs >> text=Invitations')
        ->assertPathIs(route('manage.invitations.index', $project, absolute: false))
        ->assertPresent('.shell-apart [data-active]')
        ->click('.settings-tabs >> text=Project')
        ->assertPathIs(route('manage.settings.edit', $project, absolute: false))
        ->click('.shell-nav >> text=Lottery')
        ->assertPathIs(route('lottery.show', $project, absolute: false));
});

it('keeps people out of projects they are not part of', function () {
    $project = aProject();
    $this->as(aUser());

    visit(route('projects.show', $project))->assertSee('Page not found');
    visit(route('manage.overview', $project))->assertSee('Page not found');
});

it('keeps members out of the admin area', function () {
    $project = aProject();
    $this->as(aMemberOf(aFamily($project)));

    visit(route('projects.show', $project))->assertNotPresent('.shell-apart');
    visit(route('manage.overview', $project))->assertSee('Not allowed');
});

it('suggests the federations for a project\'s organization, and takes any other', function () {
    $this->as(aUser());

    visit(route('projects.create'))
        ->fill('organization', 'FU')
        ->click('[role="option"]:has-text("FUCVAM")')
        ->assertValue('[name="organization"]', 'FUCVAM')
        ->fill('organization', 'Cooperativas del Este')
        ->fill('name', 'Cooperativa Las Acacias')
        ->fill('description', 'Forty dwellings')
        ->click('Start the project')
        ->assertPresent('.note-mini');

    expect(App\Models\Project::sole()->organization)->toBe('Cooperativas del Este');
});
