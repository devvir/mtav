<?php

use App\Models\Project;
use App\Models\User;

/**
 * Anyone with an account starts projects, which work within limits until a
 * superadmin approves them. Projects a superadmin starts are approved at once.
 */

function projectData(array $overrides = []): array
{
    return [
        'name'         => 'Cooperativa Las Acacias',
        'description'  => 'Forty units in two buildings',
        'organization' => 'FUCVAM',
        ...$overrides,
    ];
}

function theNewProject(): ?Project
{
    return Project::firstWhere('name', 'Cooperativa Las Acacias');
}

describe('Someone who signed up', function () {
    beforeEach(fn () => $this->person = aUser());

    it('starts a project, becoming its admin; it awaits approval', function () {
        $this->as($this->person)->post(route('projects.store'), projectData())->assertSessionHasNoErrors();

        expect(theNewProject())->not->toBeNull()
            ->isApproved()->toBeFalse()
            ->and($this->person->fresh()->manages(theNewProject()))->toBeTrue()
            ->and(theNewProject()->lottery)->not->toBeNull()
            ->and(theNewProject()->plan)->not->toBeNull();
    });

    it('asks a superadmin to approve the project, who is told about it', function () {
        $superadmin = aSuperadmin();
        $project = anUnapprovedProject($this->person);

        $this->as($this->person, $project)->post(route('manage.approval.request'))->assertSessionHasNoErrors();

        expect($project->fresh()->approval_requested_at)->not->toBeNull();

        $seen = $this->as($superadmin)->get(route('notifications.index'))->inertiaProps('notifications.data');

        expect(collect($seen)->pluck('title'))->toContain(__('notifications.approval_requested_title'));
    });

    it('cannot approve their own project', function () {
        $project = anUnapprovedProject($this->person);

        expect($this->as($this->person, $project)->put(route('admin.projects.approve', $project)))->toBeDenied();

        expect($project->fresh()->isApproved())->toBeFalse();
    });

    it('starts only a few projects while none is approved', function () {
        for ($i = 0; $i < config('limits.unapproved_projects_per_user'); $i++) {
            anUnapprovedProject($this->person);
        }

        expect($this->as($this->person)->post(route('projects.store'), projectData()))->toBeDenied();

        expect(theNewProject())->toBeNull();
    });

    it('needs a verified email to start a project', function () {
        $unverified = User::factory()->unverified()->create();

        $this->as($unverified)->post(route('projects.store'), projectData());

        expect(theNewProject())->toBeNull();
    });

    it('cannot reuse a project name', function () {
        aProject(['name' => 'Cooperativa Las Acacias']);

        $this->as($this->person)->post(route('projects.store'), projectData())->assertSessionHasErrors('name');
    });
});

describe('A superadmin', function () {
    beforeEach(fn () => $this->superadmin = aSuperadmin());

    it('starts an approved project', function () {
        $this->as($this->superadmin)->post(route('projects.store'), projectData())->assertSessionHasNoErrors();

        expect(theNewProject()->isApproved())->toBeTrue();
    });

    it('approves a project, and its people are told', function () {
        $project = anUnapprovedProject();
        $member = aMemberOf(aFamily($project));

        $this->as($this->superadmin, $project)->put(route('admin.projects.approve', $project))->assertSessionHasNoErrors();

        expect($project->fresh()->isApproved())->toBeTrue();

        $seen = $this->as($member)->get(route('notifications.index'))->inertiaProps('notifications.data');

        expect(collect($seen)->pluck('title'))->toContain(__('notifications.project_approved_title'));
    });

    it('sees every project on the platform in the console', function () {
        $project = anUnapprovedProject();

        $listed = $this->as($this->superadmin)->get(route('admin.projects.index'))->inertiaProps('projects.data');

        expect(collect($listed)->pluck('id'))->toContain($project->id);
    });
});

describe('An admin', function () {
    beforeEach(function () {
        $this->project = aProject();
        $this->admin = anAdminOf($this->project);
    });

    it('sees on the project\'s home what is left to set up, step by step', function () {
        $setup = fn () => $this->as($this->admin, $this->project)
            ->get(route('projects.show', $this->project))
            ->inertiaProps('setup');

        expect($setup())->toMatchArray(['unit_types' => false, 'units' => false, 'balanced' => false]);

        $type = aUnitType($this->project);
        aUnit($type);
        aFamily($this->project, $type);

        expect($setup())->toMatchArray(['unit_types' => true, 'units' => true, 'families' => true, 'balanced' => true]);
    });

    it('changes the details of a project they manage', function () {
        $this->as($this->admin, $this->project)
            ->put(route('manage.settings.update'), projectData(['name' => 'Renamed']))
            ->assertSessionHasNoErrors();

        expect($this->project->fresh()->name)->toBe('Renamed');
    });

    it('cannot change a project they do not manage', function () {
        $other = aProject(['name' => 'Original']);

        expect($this->as($this->admin, $other)->put(route('manage.settings.update'), projectData(['name' => 'Hijacked'])))
            ->toBeDenied();

        expect($other->fresh()->name)->toBe('Original');
    });

    it('deletes a project only by typing its name', function () {
        $this->as($this->admin, $this->project)
            ->delete(route('manage.settings.destroy'), ['confirmation' => 'wrong'])
            ->assertSessionHasErrors('confirmation');

        expect(Project::find($this->project->id))->not->toBeNull();

        $this->as($this->admin, $this->project)->delete(route('manage.settings.destroy'), ['confirmation' => $this->project->name]);

        expect(Project::find($this->project->id))->toBeNull();
    });

    it('lands on the project they manage after signing in', function () {
        $this->as($this->admin)->get(route('home'))->assertRedirect(route('projects.show', $this->project));
    });
});

describe('A member', function () {
    it('is not shown the setup of the project', function () {
        $project = aProject();
        $member = aMemberOf(aFamily($project));

        expect($this->as($member, $project)->get(route('projects.show', $project))->inertiaProps('setup'))->toBeNull();
    });

    it('cannot change the project', function () {
        $project = aProject(['name' => 'Original']);
        $member = aMemberOf(aFamily($project));

        expect($this->as($member, $project)->put(route('manage.settings.update'), projectData(['name' => 'Changed'])))
            ->toBeDenied();

        expect($project->fresh()->name)->toBe('Original');
    });

    it('sees the project dashboard', function () {
        $project = aProject();
        $member = aMemberOf(aFamily($project));

        $this->as($member, $project)->get(route('projects.show'))->assertOk();
    });
});

it('keeps the message of an action through the landing redirect', function () {
    $person = aUser();

    $this->as($person)->withSession(['success' => 'Welcome!'])->get(route('home'))
        ->assertRedirect(route('projects.index'));

    $this->as($person)->get(route('projects.index'))->assertSessionHas('success', 'Welcome!');
});
