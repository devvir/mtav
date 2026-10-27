<?php

uses()->group('critical');

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Every page of the three areas renders in a real browser, without JavaScript errors
 * or console noise, for the people who use it.
 */

beforeEach(function () {
    Storage::fake(Media::DISK);

    $this->project = aProject(['name' => 'Cooperativa Las Acacias']);
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project, ['name' => '2 bedrooms']);
    $this->family = aFamily($this->project, $this->type, ['name' => 'García Pérez']);
    $this->member = aMemberOf($this->family, ['firstname' => 'Ana']);
    $this->unit = aUnit($this->type, ['identifier' => 'A-101']);
    $this->event = anEvent($this->project, ['title' => 'General assembly', 'allows_rsvp' => true]);
    $this->media = Media::factory()->create(['project_id' => $this->project->id, 'owner_id' => $this->member->id, 'description' => 'Building site']);
});

it('renders the member app', function () {
    $this->as($this->member);

    $pages = [
        route('projects.show', $this->project)                  => 'Cooperativa Las Acacias',
        route('events.index', $this->project)                   => 'General assembly',
        route('events.show', [$this->project, $this->event])    => 'General assembly',
        route('media.index', $this->project)                    => 'Files',
        route('media.create', $this->project)                   => 'Share files',
        route('lottery.show', $this->project)                   => 'A-101',
        route('families.index', $this->project)                 => 'García Pérez',
        route('families.show', [$this->project, $this->family]) => 'Ana',
        route('people.index', $this->project)                   => 'Ana',
        route('people.show', [$this->project, $this->member])   => 'García Pérez',
        route('units.index', $this->project)                    => 'A-101',
        route('units.show', [$this->project, $this->unit])      => 'A-101',
        route('plan.show', $this->project)                      => 'Plan',
        route('invitations.index', $this->project)              => 'Invite to your family',
    ];

    foreach ($pages as $url => $text) {
        visit($url)->assertSee($text)->assertNoSmoke();
    }
});

it('renders the project admin area', function () {
    $this->as($this->admin);

    $pages = [
        route('manage.overview', $this->project)                       => 'Getting to the lottery',
        route('manage.unit_types.index', $this->project)               => '2 bedrooms',
        route('manage.unit_types.create', $this->project)              => 'Add a unit type',
        route('manage.unit_types.edit', [$this->project, $this->type]) => '2 bedrooms',
        route('manage.units.index', $this->project)                    => 'A-101',
        route('manage.units.create', $this->project)                   => 'Add units',
        route('manage.units.edit', [$this->project, $this->unit])      => 'A-101',
        route('manage.plan.edit', $this->project)                      => 'Save the plan',
        route('manage.families.index', $this->project)                 => 'García Pérez',
        route('manage.families.create', $this->project)                => 'Add a family',
        route('manage.families.edit', [$this->project, $this->family]) => 'García Pérez',
        route('manage.people.index', $this->project)                   => 'Ana',
        route('manage.invitations.index', $this->project)              => 'Invite someone',
        route('manage.events.index', $this->project)                   => 'General assembly',
        route('manage.events.create', $this->project)                  => 'Create an event',
        route('manage.events.edit', [$this->project, $this->event])    => 'General assembly',
        route('manage.lottery.show', $this->project)                   => 'Readiness',
        route('manage.settings.edit', $this->project)                  => 'Delete the project',
    ];

    foreach ($pages as $url => $text) {
        visit($url)->assertSee($text)->assertNoSmoke();
    }
});

it('renders the platform console and the account pages', function () {
    $this->as(aSuperadmin());

    $pages = [
        route('admin.projects.index') => 'Cooperativa Las Acacias',
        route('admin.users.index')    => 'Accounts',
        route('projects.index')       => 'Your projects',
        route('projects.create')      => 'Start a project',
        route('notifications.index')  => 'Notifications',
        route('profile.edit')         => 'Profile',
        route('password.edit')        => 'Password',
        route('appearance')           => 'Appearance',
        route('documentation.guide')  => 'Superadmin guide',
        route('documentation.faq')    => 'Frequently asked questions',
    ];

    foreach ($pages as $url => $text) {
        visit($url)->assertSee($text)->assertNoSmoke();
    }
});

it('renders the pages of people who are not signed in', function () {
    foreach ([route('login') => 'Sign in', route('register') => 'Create an account', route('password.request') => 'Forgot your password?'] as $url => $text) {
        visit($url)->assertSee($text)->assertNoSmoke();
    }
});
