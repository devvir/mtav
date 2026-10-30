<?php

use App\Enums\LotteryRunStatus;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Guests are in a project with neither a family nor a role, where it allows them. They take
 * part in everything but ranking and inviting, and never see people's contact details. A
 * project without guests lets people in the same ways, but they create their family first:
 * open is simply approved beforehand.
 */

beforeEach(function () {
    Notification::fake();

    $this->project = aProject();
    $this->type = aUnitType($this->project);
    $this->admin = anAdminOf($this->project);
    $this->family = aFamily($this->project, $this->type);
    $this->person = aUser();
});

function joinAsGuest($test): User
{
    $test->project->memberships()->create(['user_id' => $test->person->id]);

    return $test->person->fresh();
}

function lotteryDecided($project): void
{
    $project->lotteryRuns()->create([
        'uuid' => (string) Str::uuid(), 'solver' => 'test', 'started_at' => now(), 'status' => LotteryRunStatus::COMPLETED,
    ]);
}

describe('In a project with guests', function () {
    beforeEach(fn () => $this->project->update(['allows_guests' => true]));

    it('an admin invites someone with no family', function () {
        $this->as($this->admin, $this->project)->post(route('manage.invitations.store'), ['email' => 'guest@example.com'])
            ->assertSessionHasNoErrors();

        expect(Invitation::sole())->family_id->toBeNull()->is_admin->toBeFalse();
    });

    it('an admin takes someone out of their family, leaving them a guest', function () {
        $member = aMemberOf($this->family);

        $this->as($this->admin, $this->project)->put(route('manage.people.update', $member), ['family_id' => null, 'is_admin' => false])
            ->assertSessionHasNoErrors();

        expect($member->fresh()->isGuestIn($this->project))->toBeTrue();
    });

    it('a guest uses the forum, the files, the chat and events, and sees the lottery', function () {
        $guest = joinAsGuest($this);

        $this->as($guest, $this->project)->get(route('forum.index'))->assertOk();
        $this->as($guest, $this->project)->post(route('forum.store'), ['title' => 'Hello there', 'body' => 'Hi everyone'])->assertSessionHasNoErrors();
        $this->as($guest, $this->project)->get(route('media.index'))->assertOk();
        $this->as($guest, $this->project)->get(route('events.index'))->assertOk();
        $this->as($guest, $this->project)->get(route('chat.index'))->assertOk();
        $this->as($guest, $this->project)->get(route('lottery.show'))->assertOk();

        expect($guest->can('create', [App\Models\Media::class, $this->project]))->toBeTrue();
    });

    it('a guest neither ranks nor invites, and is offered neither', function () {
        $guest = joinAsGuest($this);
        $page = $this->as($guest, $this->project)->get(route('lottery.show'));

        expect($page->inertiaProps('canRank'))->toBeFalse()
            ->and($page->inertiaProps('project.can.rank'))->toBeFalse()
            ->and($page->inertiaProps('project.can.invite'))->toBeFalse()
            ->and($page->inertiaProps('project.is_guest'))->toBeTrue();

        expect($this->as($guest, $this->project)->post(route('invitations.store'), ['email' => 'x@example.com']))->toBeDenied();
        expect($this->as($guest, $this->project)->get(route('invitations.index'))->status())->toBe(404);
    });

    it('a guest never sees people\'s email or phone, even shared, nor finds anyone by them', function () {
        $guest = joinAsGuest($this);
        $member = aMemberOf($this->family, ['email' => 'shared@example.com', 'phone' => '099', 'email_public' => true, 'phone_public' => true]);

        $seen = collect($this->as($guest, $this->project)->get(route('people.index'))->inertiaProps('people.data'))->firstWhere('id', $member->id);
        expect($seen)->not->toHaveKey('email')->not->toHaveKey('phone');

        $found = $this->as($guest, $this->project)->get(route('people.index', ['q' => 'shared@example.com', 'family' => $this->family->id]));
        expect(User::query()->whereKey($member->id)->search('shared@example.com', nameOnly: true)->exists())->toBeFalse()
            ->and(User::query()->whereKey($member->id)->search('shared@example.com')->exists())->toBeTrue();
        $found->assertOk();

        // A member sees what they share, and a guest's own stays theirs
        $other = aMemberOf($this->family);
        $byMember = collect($this->as($other, $this->project)->get(route('people.index'))->inertiaProps('people.data'))->firstWhere('id', $member->id);
        expect($byMember['email'])->toBe('shared@example.com');
    });

    it('is shown as a guest', function () {
        $guest = joinAsGuest($this);

        $seen = collect($this->as($this->admin, $this->project)->get(route('people.index'))->inertiaProps('people.data'))->firstWhere('id', $guest->id);
        expect($seen['is_guest'])->toBeTrue();
    });

    it('takes guests even once the lottery decided, in a project listed and open', function () {
        $this->project->update(['listed' => true, 'open_to_join' => true]);
        lotteryDecided($this->project);

        $this->as($this->person)->post(route('directory.join', $this->project))->assertRedirect(route('projects.show', $this->project));
        expect($this->person->fresh()->isGuestIn($this->project))->toBeTrue();
    });
});

describe('Turning guests off', function () {
    beforeEach(fn () => $this->project->update(['allows_guests' => true]));

    $settings = fn ($test, array $data) => $test->as($test->admin, $test->project)->put(route('manage.settings.update'), [
        'name' => $test->project->name, 'description' => $test->project->description, ...$data,
    ]);

    it('is refused while there are guests', function () use ($settings) {
        joinAsGuest($this);

        $settings($this, ['allows_guests' => false])->assertSessionHasErrors('allows_guests');
        expect($this->project->fresh()->allows_guests)->toBeTrue();
    });

    it('is refused while an invitation with no family is on its way', function () use ($settings) {
        $this->as($this->admin, $this->project)->post(route('manage.invitations.store'), ['email' => 'guest@example.com']);

        $settings($this, ['allows_guests' => false])->assertSessionHasErrors('allows_guests');
    });

    it('works once there are none', function () use ($settings) {
        $settings($this, ['allows_guests' => false])->assertSessionHasNoErrors();
        expect($this->project->fresh()->allows_guests)->toBeFalse();
    });

    it('leaves an admin with no family alone', function () use ($settings) {
        expect($this->admin->fresh()->isGuestIn($this->project))->toBeFalse();
        $settings($this, ['allows_guests' => false])->assertSessionHasNoErrors();
    });
});

describe('In a project without guests', function () {
    it('nobody is invited with neither a family nor a role', function () {
        $this->as($this->admin, $this->project)->post(route('manage.invitations.store'), ['email' => 'x@example.com'])
            ->assertSessionHasErrors('family_id');
    });

    it('nobody is left with neither a family nor a role', function () {
        $member = aMemberOf($this->family);

        $this->as($this->admin, $this->project)->put(route('manage.people.update', $member), ['family_id' => null, 'is_admin' => false])
            ->assertSessionHasErrors('family_id');
    });

    it('whoever joins an open project creates their family before anything else', function () {
        $this->project->update(['listed' => true, 'open_to_join' => true]);

        $this->as($this->person)->post(route('directory.join', $this->project))->assertRedirect(route('projects.show', $this->project));
        $person = $this->person->fresh();

        $this->as($person, $this->project)->get(route('projects.show'))->assertRedirect(route('family.create', $this->project));
        $this->as($person, $this->project)->get(route('forum.index'))->assertRedirect(route('family.create', $this->project));

        $this->as($person, $this->project)->get(route('family.create'))->assertInertia(fn ($page) => $page->component('Member/OwnFamily'));
        $this->as($person, $this->project)->post(route('families.store'), ['name' => 'Ours', 'unit_type_id' => $this->type->id])->assertSessionHasNoErrors();

        $person = $person->fresh();
        expect($person->familyIn($this->project)?->name)->toBe('Ours');
        $this->as($person, $this->project)->get(route('forum.index'))->assertOk();
        $this->as($person, $this->project)->get(route('family.create'))->assertRedirect(route('projects.show', $this->project));
    });

    it('whoever an admin accepts does the same: open is simply approved beforehand', function () {
        $this->project->update(['listed' => true]);
        $this->as($this->person)->post(route('directory.ask', $this->project));

        $this->as($this->admin, $this->project)->post(route('manage.join_requests.accept', App\Models\JoinRequest::sole()));
        $person = $this->person->fresh();

        expect($person->needsFamilyIn($this->project))->toBeTrue();
        $this->as($person, $this->project)->get(route('projects.show'))->assertRedirect(route('family.create', $this->project));
    });

    it('someone of a family adds them at once instead', function () {
        $this->project->memberships()->create(['user_id' => $this->person->id]);

        $this->as(aMemberOf($this->family), $this->project)->post(route('invitations.store'), ['email' => $this->person->email])->assertSessionHasNoErrors();

        expect($this->person->fresh()->familyIn($this->project)?->id)->toBe($this->family->id);
    });

    it('they may leave instead', function () {
        $this->project->memberships()->create(['user_id' => $this->person->id]);

        $this->as($this->person->fresh(), $this->project)->delete(route('membership.leave'))->assertRedirect();

        expect($this->person->fresh()->canAccess($this->project))->toBeFalse();
    });

    it('closes to newcomers once the lottery decided', function () {
        $this->project->update(['listed' => true, 'open_to_join' => true]);
        lotteryDecided($this->project);

        $seen = collect($this->as($this->person)->get(route('directory.index'))->inertiaProps('listing.data'))->firstWhere('id', $this->project->id);
        expect($seen['standing'])->toBe('closed');
        expect($this->as($this->person)->post(route('directory.join', $this->project)))->toBeDenied();
    });

    it('closes to newcomers while there are no unit types to choose from', function () {
        $project = aProject(['listed' => true]);

        $seen = collect($this->as($this->person)->get(route('directory.index'))->inertiaProps('listing.data'))->firstWhere('id', $project->id);
        expect($seen['standing'])->toBe('closed');
        expect($this->as($this->person)->post(route('directory.ask', $project)))->toBeDenied();
    });
});

describe('A superadmin', function () {
    it('can be admin and in a family too, shown as anyone else', function () {
        $superadmin = aSuperadmin();
        $this->project->memberships()->create(['user_id' => $superadmin->id, 'is_admin' => true, 'family_id' => $this->family->id]);
        $superadmin = $superadmin->fresh();

        $seen = collect($this->as($this->admin, $this->project)->get(route('people.index'))->inertiaProps('people.data'))->firstWhere('id', $superadmin->id);
        expect($seen['is_admin'])->toBeTrue()
            ->and($seen['family']['id'])->toBe($this->family->id)
            ->and($seen)->not->toHaveKey('is_superadmin')
            ->and($superadmin->can('rankUnits', $this->project))->toBeTrue();
    });

    it('does anything an admin can in a project they are not in; ranking takes a family', function () {
        $superadmin = aSuperadmin();

        expect($superadmin->can('update', $this->project))->toBeTrue()
            ->and($superadmin->can('runLottery', $this->project))->toBeTrue()
            ->and($superadmin->can('invite', [$this->project, $this->family]))->toBeTrue()
            ->and($superadmin->can('rankUnits', $this->project))->toBeFalse();

        $this->as($superadmin, $this->project)->post(route('manage.invitations.store'), ['email' => 'x@example.com', 'family_id' => $this->family->id])
            ->assertSessionHasNoErrors();
    });
});
