<?php

use App\Models\Project;

/**
 * Projects: starting one, approval and its limits, settings, switching between them.
 */

it('starts a project that works within limits, and asks for approval', function () {
    $person = aUser();
    $this->as($person);

    visit(route('projects.create'))
        ->assertSee('Until it is approved')
        ->fill('name', 'Cooperativa Las Acacias')
        ->fill('description', 'Forty dwellings')
        ->click('Start the project')
        ->assertSee('This project is awaiting approval: some limits apply.')
        ->click(button('Ask for approval'))
        ->click(inDialog('Ask for approval'))
        ->assertSee('We asked the platform team to approve your project.')
        ->assertSee('Approval requested: limits apply until a superadmin approves the project.')
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
        ->assertSee('Your project is approved')
        ->click('Your project is approved')
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

it('shows no choice of projects to someone in only one', function () {
    $project = aProject(['name' => 'Cooperativa Uno']);
    $this->as(aMemberOf(aFamily($project)), $project);

    visit(route('projects.show', $project))
        ->assertSee('Cooperativa Uno')
        ->assertMissing('header .shell-switcher')
        ->click('[aria-label="Your account"]')
        ->assertDontSeeIn('[role="menu"]', 'Your projects');

    // Their account pages keep the project's places at hand
    visit(route('profile.edit'))
        ->assertSeeIn('.shell-nav', 'Lottery')
        ->assertDontSeeIn('.shell-nav', 'Your projects');
});

it('gives admins the project\'s places and an administration group in one sidebar', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    visit(route('projects.show', $project))
        ->assertSeeIn('.shell-nav', 'Administration')
        ->click('.shell-nav >> text=Setup')
        ->assertPathIs(route('manage.overview', $project, absolute: false))
        ->assertSeeIn('.shell-nav', 'Lottery')
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

    visit(route('projects.show', $project))->assertDontSeeIn('.shell-nav', 'Administration');
    visit(route('manage.overview', $project))->assertSee('Not allowed');
});
