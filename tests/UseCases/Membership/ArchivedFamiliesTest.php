<?php

use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Notification as Notifications;

/**
 * An archived family is not free for the taking: those who were in it come back to it, admins add
 * to it, and anyone else who picks its name just has a new family. Names may repeat. Admins can
 * delete an archived family for good, once they have seen which one it is.
 */

function startsFamily($test, User $who, string $name = 'Pereira')
{
    return $test->as($who, $test->project)->post(route('families.store'), ['name' => $name, 'unit_type_id' => $test->type->id]);
}

function aPersonWithNoFamily($project): User
{
    $person = aUser();
    $project->memberships()->create(['user_id' => $person->id]);

    return $person;
}

/** A family of the project that had someone and lost them, with a ranking they left behind */
function anArchivedFamily($test, string $name = 'Pereira'): array
{
    $family = aFamily($test->project, $test->type, ['name' => $name]);
    $former = aMemberOf($family);
    $family->preferences()->attach(aUnit($test->type)->id, ['rank' => 1]);
    $test->as($former, $test->project)->delete(route('family.leave'))->assertSessionHasNoErrors();

    expect($family->fresh()->trashed())->toBeTrue();

    return [$family, $former];
}

beforeEach(function () {
    Notifications::fake();
    $this->project = aProject(['allows_guests' => true]);
    $this->type = aUnitType($this->project);
    $this->admin = anAdminOf($this->project);
});

describe('Taking the name of an archived family', function () {
    it('brings it back, ranking included, for someone who was in it', function () {
        [$family, $former] = anArchivedFamily($this);

        startsFamily($this, $former)->assertSessionHasNoErrors();

        expect($family->fresh()->trashed())->toBeFalse()
            ->and($former->fresh()->familyIn($this->project)->id)->toBe($family->id)
            ->and($family->preferences()->count())->toBe(1);
    });

    it('brings it back for someone who left the project and came back', function () {
        [$family, $former] = anArchivedFamily($this);
        $former->membershipIn($this->project)->delete();
        $this->project->memberships()->create(['user_id' => $former->id]);

        startsFamily($this, $former->fresh())->assertSessionHasNoErrors();

        expect($former->fresh()->familyIn($this->project)->id)->toBe($family->id);
    });

    it('gives anyone else a new family, with nothing of the archived one', function () {
        [$family] = anArchivedFamily($this);
        $stranger = aPersonWithNoFamily($this->project);

        startsFamily($this, $stranger)->assertSessionHasNoErrors();

        $theirs = $stranger->fresh()->familyIn($this->project);

        expect($family->fresh()->trashed())->toBeTrue()
            ->and($theirs->id)->not->toBe($family->id)
            ->and($theirs->name)->toBe('Pereira')
            ->and($theirs->preferences()->count())->toBe(0);
    });

    it('finds the one they were in, among several of that name', function () {
        [$theirs, $former] = anArchivedFamily($this);
        [$another] = anArchivedFamily($this);

        startsFamily($this, $former)->assertSessionHasNoErrors();

        expect($theirs->fresh()->trashed())->toBeFalse()
            ->and($another->fresh()->trashed())->toBeTrue();
    });

    it('does not bring back a family that declined them: they get a new one', function () {
        [$family, $former] = anArchivedFamily($this);
        FamilyJoinRequest::create(['project_id' => $this->project->id, 'family_id' => $family->id, 'user_id' => $former->id, 'declined_at' => now()]);

        startsFamily($this, $former)->assertSessionHasNoErrors();

        expect($family->fresh()->trashed())->toBeTrue()
            ->and($former->fresh()->familyIn($this->project)->id)->not->toBe($family->id);
    });

    it('leaves it closed to anyone asking to join', function () {
        [$family] = anArchivedFamily($this);

        expect($this->as(aPersonWithNoFamily($this->project), $this->project)->post(route('family.requests.store', $family->id)))
            ->toBeDenied();

        expect(FamilyJoinRequest::count())->toBe(0);
    });

    it('still lets an admin add someone to it', function () {
        [$family] = anArchivedFamily($this);
        $person = aPersonWithNoFamily($this->project);

        $this->as($this->admin, $this->project)
            ->put(route('manage.people.update', $person), ['family_id' => $family->id, 'is_admin' => false])
            ->assertSessionHasNoErrors();

        expect($family->fresh()->trashed())->toBeFalse()
            ->and($person->fresh()->familyIn($this->project)->id)->toBe($family->id);
    });
});

describe('The record of who was in a family', function () {
    it('holds everyone who came in, however they did, and stays when they go', function () {
        $family = aFamily($this->project, $this->type);
        $created = aMemberOf($family);
        $moved = aPersonWithNoFamily($this->project);
        $this->as($this->admin, $this->project)
            ->put(route('manage.people.update', $moved), ['family_id' => $family->id, 'is_admin' => false]);
        $asked = aPersonWithNoFamily($this->project);
        app(App\Services\FamilyService::class)->accept(FamilyJoinRequest::create(['project_id' => $this->project->id, 'family_id' => $family->id, 'user_id' => $asked->id]), $created);

        $moved->membershipIn($this->project)->update(['family_id' => null]);
        $asked->membershipIn($this->project)->delete();

        expect($family->everyone()->pluck('users.id')->sort()->values()->all())
            ->toBe(collect([$created->id, $moved->id, $asked->id])->sort()->values()->all());
    });

    it('counts someone once, however many times they come back', function () {
        $family = aFamily($this->project, $this->type);
        $other = aFamily($this->project, $this->type);
        $person = aMemberOf($family);
        aMemberOf($other);

        $person->membershipIn($this->project)->update(['family_id' => $other->id]);
        $person->fresh()->membershipIn($this->project)->update(['family_id' => $family->id]);

        expect($family->everyone()->count())->toBe(1);
    });

    it('goes with the account', function () {
        [$family, $former] = anArchivedFamily($this);

        $former->delete();

        expect($family->everyone()->count())->toBe(0);
    });
});

describe('Repeated names', function () {
    it('are fine for admins adding a family, or renaming one', function () {
        aFamily($this->project, $this->type, ['name' => 'García']);
        $other = aFamily($this->project, $this->type, ['name' => 'Otra']);

        $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
            'name' => 'García', 'unit_type_id' => $this->type->id, 'email' => 'ana@example.com',
        ])->assertSessionHasNoErrors();
        $this->as($this->admin, $this->project)->put(route('manage.families.update', $other), [
            'name' => 'García', 'unit_type_id' => $this->type->id,
        ])->assertSessionHasNoErrors();

        expect($this->project->families()->where('name', 'García')->count())->toBe(3);
    });

    it('are fine for someone starting theirs', function () {
        $existing = aFamily($this->project, $this->type, ['name' => 'García']);
        aMemberOf($existing);
        $person = aPersonWithNoFamily($this->project);

        startsFamily($this, $person, 'García')->assertSessionHasNoErrors();

        expect($person->fresh()->familyIn($this->project)->id)->not->toBe($existing->id);
    });

    it('say who is in each, in the lists that pick a family by name', function () {
        $one = aFamily($this->project, $this->type, ['name' => 'García']);
        aMemberOf($one, ['firstname' => 'Ana']);
        $other = aFamily($this->project, $this->type, ['name' => 'garcía']);
        aMemberOf($other, ['firstname' => 'Bruno']);
        $empty = aFamily($this->project, $this->type, ['name' => 'García']);
        $alone = aFamily($this->project, $this->type, ['name' => 'Silva']);

        $named = collect($this->as($this->admin, $this->project)->get(route('people.index'))->inertiaProps('families'))->pluck('name', 'id');

        expect($named[$one->id])->toBe('García (Ana)')
            ->and($named[$other->id])->toBe('garcía (Bruno)')
            ->and($named[$empty->id])->toBe("García (#{$empty->id})")
            ->and($named[$alone->id])->toBe('Silva');
    });

    it('do not get in the way of an invitation list either', function () {
        aMemberOf(aFamily($this->project, $this->type, ['name' => 'García']), ['firstname' => 'Ana']);
        aMemberOf(aFamily($this->project, $this->type, ['name' => 'García']), ['firstname' => 'Bruno']);

        $names = collect($this->as($this->admin, $this->project)->get(route('manage.invitations.index'))->inertiaProps('families'))->pluck('name');

        expect($names->sort()->values()->all())->toBe(['García (Ana)', 'García (Bruno)']);
    });
});

describe('An archived family, for admins', function () {
    it('shows its history where archived families are listed', function () {
        [$family, $former] = anArchivedFamily($this);

        $item = $this->as($this->admin, $this->project)->get(route('families.index', ['archived' => 1]))->inertiaProps('archive')['items'][0];

        expect($item)->id->toBe($family->id)
            ->title->toBe('Pereira')
            ->people->toBe([$former->name])
            ->created_at->not->toBeNull()
            ->deleted_at->not->toBeNull();
    });

    it('can be deleted for good, telling nobody', function () {
        [$family] = anArchivedFamily($this);
        $notifications = Notification::count();

        $this->as($this->admin, $this->project)->delete(route('manage.archive.families.destroy', $family->id))
            ->assertSessionHas('success');

        expect(Family::withTrashed()->find($family->id))->toBeNull()
            ->and(Notification::count())->toBe($notifications);
        Notifications::assertNothingSent();
    });

    it('can be deleted however it stands: with a unit, or during a lottery', function () {
        [$family] = anArchivedFamily($this);
        $unit = aUnit($this->type);
        $unit->forceFill(['family_id' => $family->id])->save();
        aLotteryInProgress($this->project);

        $this->as($this->admin, $this->project)->delete(route('manage.archive.families.destroy', $family->id))
            ->assertSessionHasNoErrors();

        expect(Family::withTrashed()->find($family->id))->toBeNull()
            ->and($unit->fresh()->family_id)->toBeNull();
    });

    it('is only for the project\'s admins', function () {
        [$family, $former] = anArchivedFamily($this);
        $elsewhere = anAdminOf(aProject());

        expect($this->as($former, $this->project)->delete(route('manage.archive.families.destroy', $family->id)))->toBeDenied();
        expect($this->as($elsewhere, $this->project)->delete(route('manage.archive.families.destroy', $family->id)))->toBeDenied();

        expect($family->fresh())->not->toBeNull();
    });

    it('is the only family that can go this way', function () {
        $active = aFamily($this->project, $this->type);
        $foreignProject = aProject();
        $foreign = aFamily($foreignProject, aUnitType($foreignProject));
        $foreign->delete();

        expect($this->as($this->admin, $this->project)->delete(route('manage.archive.families.destroy', $active->id)))->toBeDenied();
        expect($this->as($this->admin, $this->project)->delete(route('manage.archive.families.destroy', $foreign->id)))->toBeDenied();

        expect($active->fresh()->trashed())->toBeFalse()
            ->and($foreign->fresh())->not->toBeNull();
    });
});
