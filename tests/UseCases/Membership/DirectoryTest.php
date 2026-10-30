<?php

use App\Models\JoinRequest;
use App\Models\Project;
use App\Notifications\JoinedNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Admins may list a project for anyone to find. Where it is open people join at once; where
 * it is not they ask, and the admins answer. Either way they enter with no family, until they
 * create theirs or someone of it adds them.
 */

beforeEach(function () {
    Notification::fake();

    $this->project = aProject();
    $this->project->update(['listed' => true]);
    $this->admin = anAdminOf($this->project);
    $this->person = aUser();
});

function listedNames($test, $user): array
{
    return collect($test->as($user)->get(route('directory.index'))->inertiaProps('listing.data'))->pluck('name')->all();
}

function standingIn($test, $user, Project $project): string
{
    return collect($test->as($user)->get(route('directory.index'))->inertiaProps('listing.data'))
        ->firstWhere('id', $project->id)['standing'];
}

describe('The directory', function () {
    it('shows the projects their admins list, and no other', function () {
        $hidden = aProject();

        expect(listedNames($this, $this->person))->toContain($this->project->name)->not->toContain($hidden->name);
    });

    it('does not show a project the platform has not approved yet, whatever its admins chose', function () {
        $unapproved = anUnapprovedProject();
        $unapproved->update(['listed' => true, 'open_to_join' => true]);

        expect(listedNames($this, $this->person))->not->toContain($unapproved->name);
        expect($this->as($this->person)->post(route('directory.join', $unapproved)))->toBeDenied();
    });

    it('does not show an archived project', function () {
        $this->project->delete();

        expect(listedNames($this, $this->person))->not->toContain($this->project->name);
    });

    it('finds by name, organization or description', function () {
        $this->project->update(['name' => 'Los Pinos', 'organization' => 'FUCVAM', 'description' => 'Cerca del mar']);
        $names = fn (string $q) => collect($this->as($this->person)->get(route('directory.index', ['q' => $q]))->inertiaProps('listing.data'))->pluck('name')->all();

        expect($names('Pinos'))->toBe(['Los Pinos'])
            ->and($names('fucvam'))->toBe(['Los Pinos'])
            ->and($names('mar'))->toBe(['Los Pinos'])
            ->and($names('nada que ver'))->toBe([]);
    });

    it('tells those in the project they are in', function () {
        expect(standingIn($this, $this->admin, $this->project))->toBe('in');
    });

    it('needs an account', function () {
        $this->get(route('directory.index'))->assertRedirect(route('login'));
    });
});

describe('Admins', function () {
    it('choose whether the project is listed, and who can join', function () {
        $this->as($this->admin, $this->project)->put(route('manage.settings.update'), [
            'name'         => $this->project->name,
            'description'  => $this->project->description,
            'listed'       => true,
            'open_to_join' => true,
        ])->assertSessionHasNoErrors();

        expect($this->project->fresh())->listed->toBeTrue()->open_to_join->toBeTrue();
    });

    it('are the only ones who do', function () {
        $member = aMemberOf(aFamily($this->project));

        $response = $this->as($member, $this->project)->put(route('manage.settings.update'), [
            'name' => 'Taken over', 'description' => 'x y', 'listed' => false,
        ]);

        expect($response)->toBeDenied();
        expect($this->project->fresh()->listed)->toBeTrue();
    });
});

describe('In an open project', function () {
    beforeEach(fn () => $this->project->update(['open_to_join' => true]));

    it('someone joins at once, with no family', function () {
        $this->as($this->person)->post(route('directory.join', $this->project))
            ->assertRedirect(route('projects.show', $this->project));

        $person = $this->person->fresh();
        expect($person->canAccess($this->project))->toBeTrue()
            ->and($person->familyIn($this->project))->toBeNull()
            ->and($person->manages($this->project))->toBeFalse();
    });

    it('their joining reaches the project', function () {
        $this->as($this->person)->post(route('directory.join', $this->project));

        $seen = $this->as($this->admin)->get(route('notifications.index'))->inertiaProps('notifications.data');
        expect(collect($seen)->pluck('title'))->toContain(__('notifications.member_joined_title'));
    });

    it('settles a request they had made before it opened', function () {
        JoinRequest::create(['project_id' => $this->project->id, 'user_id' => $this->person->id]);

        expect(standingIn($this, $this->person, $this->project))->toBe('can_join');

        $this->as($this->person)->post(route('directory.join', $this->project));

        expect(JoinRequest::count())->toBe(0);
    });

    it('someone already in does not join twice, nor lose their family', function () {
        $member = aMemberOf(aFamily($this->project));

        expect($this->as($member)->post(route('directory.join', $this->project)))->toBeDenied();
        expect($member->fresh()->familyIn($this->project))->not->toBeNull();
    });

    it('a superadmin passes through nothing: they only look', function () {
        expect($this->as(aSuperadmin())->post(route('directory.join', $this->project)))->toBeDenied();
    });
});

describe('In a project by request', function () {
    it('nobody joins without asking', function () {
        expect($this->as($this->person)->post(route('directory.join', $this->project)))->toBeDenied();
        expect($this->person->fresh()->canAccess($this->project))->toBeFalse();
    });

    it('someone asks, and every admin hears of it', function () {
        $this->as($this->person)->post(route('directory.ask', $this->project))->assertSessionHasNoErrors();

        expect(JoinRequest::sole())->user_id->toBe($this->person->id)
            ->and($this->person->fresh()->canAccess($this->project))->toBeFalse();

        $seen = $this->as($this->admin)->get(route('notifications.index'))->inertiaProps('notifications.data');
        expect(collect($seen)->pluck('title'))->toContain(__('notifications.join_requested_title'));
        expect($this->as($this->admin, $this->project)->get(route('manage.overview'))->inertiaProps('project.join_requests'))->toBe(1);
    });

    it('someone asks once, until they get an answer', function () {
        $this->as($this->person)->post(route('directory.ask', $this->project));

        expect(standingIn($this, $this->person, $this->project))->toBe('asked');
        expect($this->as($this->person)->post(route('directory.ask', $this->project)))->toBeDenied();
    });

    it('someone withdraws what they asked', function () {
        $this->as($this->person)->post(route('directory.ask', $this->project));
        $this->as($this->person)->delete(route('directory.withdraw', $this->project));

        expect(JoinRequest::count())->toBe(0)
            ->and(standingIn($this, $this->person, $this->project))->toBe('can_ask');
    });

    it('what they asked stays in sight when the project stops being listed', function () {
        $this->as($this->person)->post(route('directory.ask', $this->project));
        $this->project->update(['listed' => false]);

        expect(standingIn($this, $this->person, $this->project))->toBe('asked');
    });

    it('an admin sees who asks, and accepts them: they are in, with no family, and told by email', function () {
        $this->as($this->person)->post(route('directory.ask', $this->project));

        $requests = $this->as($this->admin, $this->project)->get(route('manage.invitations.index'))->inertiaProps('requests');
        expect($requests)->toHaveCount(1)
            ->and($requests[0]['email'])->toBe($this->person->email);

        $this->as($this->admin, $this->project)->post(route('manage.join_requests.accept', JoinRequest::sole()))
            ->assertSessionHasNoErrors();

        $person = $this->person->fresh();
        expect($person->canAccess($this->project))->toBeTrue()
            ->and($person->familyIn($this->project))->toBeNull()
            ->and(JoinRequest::count())->toBe(0);

        Notification::assertSentTo($person, JoinedNotification::class, fn ($notice) => ! $notice->addedBy);
        $seen = $this->as($person)->get(route('notifications.index'))->inertiaProps('notifications.data');
        expect(collect($seen)->pluck('title'))->toContain(__('notifications.join_accepted_title'));
    });

    it('an admin declines: they stay out, hear of it, and cannot ask again for a while', function () {
        $this->as($this->person)->post(route('directory.ask', $this->project));

        $this->as($this->admin, $this->project)->delete(route('manage.join_requests.decline', JoinRequest::sole()))
            ->assertSessionHasNoErrors();

        expect($this->person->fresh()->canAccess($this->project))->toBeFalse()
            ->and(standingIn($this, $this->person, $this->project))->toBe('declined');
        expect($this->as($this->person)->post(route('directory.ask', $this->project)))->toBeDenied();

        $seen = $this->as($this->person)->get(route('notifications.index'))->inertiaProps('notifications.data');
        expect(collect($seen)->pluck('title'))->toContain(__('notifications.join_declined_title'));

        $this->travel(config('limits.join_request_retry_days') + 1)->days();

        expect(standingIn($this, $this->person, $this->project))->toBe('can_ask');
        $this->as($this->person)->post(route('directory.ask', $this->project))->assertSessionHasNoErrors();
        expect(JoinRequest::sole()->isPending())->toBeTrue();
    });

    it('only its admins answer', function () {
        $this->as($this->person)->post(route('directory.ask', $this->project));
        $outsider = anAdminOf(aProject());
        $member = aMemberOf(aFamily($this->project));

        expect($this->as($member, $this->project)->post(route('manage.join_requests.accept', JoinRequest::sole())))->toBeDenied();
        expect($this->as($outsider, $this->project)->post(route('manage.join_requests.accept', JoinRequest::sole())))->toBeDenied();

        expect($this->person->fresh()->canAccess($this->project))->toBeFalse();
    });

    it('an answer cannot be given twice', function () {
        $this->as($this->person)->post(route('directory.ask', $this->project));
        $request = JoinRequest::sole();
        $this->as($this->admin, $this->project)->delete(route('manage.join_requests.decline', $request));

        $this->as($this->admin, $this->project)->post(route('manage.join_requests.accept', $request))->assertNotFound();
    });

    it('a request of another project is not answered from this one', function () {
        $other = aProject();
        $request = JoinRequest::create(['project_id' => $other->id, 'user_id' => $this->person->id]);

        $this->as($this->admin, $this->project)->post(route('manage.join_requests.accept', $request))->assertNotFound();
    });
});

describe('Someone in the project with no family', function () {
    beforeEach(function () {
        $this->project->update(['open_to_join' => true]);
        $this->as($this->person)->post(route('directory.join', $this->project));
        $this->person = $this->person->fresh();
        $this->type = aUnitType($this->project);
    });

    it('is offered to create theirs', function () {
        $types = $this->as($this->person, $this->project)->get(route('projects.show'))->inertiaProps('ownFamilyTypes');

        expect(collect($types)->pluck('id')->all())->toBe([$this->type->id]);
    });

    it('creates their family, and is in it', function () {
        $this->as($this->person, $this->project)->post(route('families.store'), [
            'name' => 'Rodríguez Silva', 'unit_type_id' => $this->type->id,
        ])->assertSessionHasNoErrors();

        expect($this->person->fresh()->familyIn($this->project)?->name)->toBe('Rodríguez Silva');
    });

    it('creates only one', function () {
        $this->as($this->person, $this->project)->post(route('families.store'), ['name' => 'One', 'unit_type_id' => $this->type->id]);

        expect($this->as($this->person->fresh(), $this->project)->post(route('families.store'), ['name' => 'Two', 'unit_type_id' => $this->type->id]))->toBeDenied();
        expect($this->project->families()->where('name', 'Two')->exists())->toBeFalse();
    });

    it('cannot create it once the lottery decided', function () {
        $this->project->lotteryRuns()->create(['uuid' => (string) Illuminate\Support\Str::uuid(), 'solver' => 'test', 'started_at' => now(), 'status' => App\Enums\LotteryRunStatus::COMPLETED]);

        expect($this->as($this->person, $this->project)->post(route('families.store'), ['name' => 'Late', 'unit_type_id' => $this->type->id]))->toBeDenied();
    });

    it('cannot rank units or invite anyone', function () {
        expect($this->as($this->person, $this->project)->post(route('invitations.store'), ['email' => 'x@example.com']))->toBeDenied();
        expect($this->person->can('rankUnits', $this->project))->toBeFalse();
    });

    it('enters a family at once when someone of it adds them: told in the app and by email', function () {
        $family = aFamily($this->project, $this->type);
        $relative = aMemberOf($family);

        $this->as($relative, $this->project)->post(route('invitations.store'), ['email' => $this->person->email])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', __('flash.added_to_family', ['name' => $this->person->name, 'family' => $family->name]));

        expect($this->person->fresh()->familyIn($this->project)?->id)->toBe($family->id)
            ->and($this->project->invitations()->count())->toBe(0);

        Notification::assertSentTo($this->person, JoinedNotification::class, fn ($notice) => $notice->addedBy?->is($relative));
        $seen = $this->as($this->person->fresh())->get(route('notifications.index'))->inertiaProps('notifications.data');
        expect(collect($seen)->pluck('title'))->toContain(__('notifications.added_to_family_title'));
    });

    it('enters a family at once when an admin adds them too', function () {
        $family = aFamily($this->project, $this->type);

        $this->as($this->admin, $this->project)->post(route('manage.invitations.store'), [
            'email' => $this->person->email, 'family_id' => $family->id,
        ])->assertSessionHasNoErrors();

        expect($this->person->fresh()->familyIn($this->project)?->id)->toBe($family->id);
    });

    it('has any invitation waiting for them settled by it', function () {
        $family = aFamily($this->project, $this->type);
        $this->project->invitations()->create([
            'email' => $this->person->email, 'token_hash' => 'x', 'expires_at' => now()->addDay(), 'family_id' => $family->id,
        ]);

        $this->as(aMemberOf($family), $this->project)->post(route('invitations.store'), ['email' => $this->person->email]);

        expect($this->project->invitations()->count())->toBe(0);
    });
});

it('still invites, to accept, someone with an account who is not in the project', function () {
    $family = aFamily($this->project);
    $relative = aMemberOf($family);

    $this->as($relative, $this->project)->post(route('invitations.store'), ['email' => $this->person->email])
        ->assertSessionHas('success', __('flash.invitation_sent', ['email' => $this->person->email]));

    expect($this->person->fresh()->canAccess($this->project))->toBeFalse()
        ->and($this->project->invitations()->pending()->count())->toBe(1);
});

it('never moves someone from their family by adding them to another', function () {
    $person = aMemberOf(aFamily($this->project));
    $other = aMemberOf(aFamily($this->project));

    $this->as($other, $this->project)->post(route('invitations.store'), ['email' => $person->email])
        ->assertSessionHasErrors('email');
});
