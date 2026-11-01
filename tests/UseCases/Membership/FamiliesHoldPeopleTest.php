<?php

use App\Enums\NotificationType;
use App\Jobs\DiscardFamilyIfEmpty;
use App\Models\Family;
use App\Models\FamilyJoinRequest;
use App\Models\Invitation;
use App\Models\Notification;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\FamilyRequestNotification;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Notification as Notifications;
use Illuminate\Support\Str;

/**
 * A family holds people: someone finds theirs and asks, anyone in it answers, and a family with
 * nobody left is archived, to come back when someone is invited into it.
 */

function aGuestIn($project): User
{
    $guest = User::factory()->create();
    $project->memberships()->create(['user_id' => $guest->id]);

    return $guest;
}

function toldOf($user, NotificationType $type): bool
{
    return Notification::where('type', $type)->where('target_id', $user->id)->exists();
}

beforeEach(function () {
    Notifications::fake();
    $this->project = aProject(['allows_guests' => true]);
    $this->type = aUnitType($this->project);
    $this->family = aFamily($this->project, $this->type, ['name' => 'Pereira']);
    $this->member = aMemberOf($this->family);
    $this->admin = anAdminOf($this->project);
    $this->guest = aGuestIn($this->project);
    $this->ask = fn ($who = null, $family = null) => $this->as($who ?? $this->guest, $this->project)
        ->post(route('family.requests.store', $family ?? $this->family));
});

describe('Finding one\'s family', function () {
    it('shows the families by name and people, with no contact details', function () {
        $props = $this->as($this->guest, $this->project)->get(route('family.create'))->viewData('page')['props'];

        expect($props['families'][0])->name->toBe('Pereira')->people->toContain($this->member->name)
            ->and(json_encode($props['families']))->not->toContain($this->member->email);
    });

    it('is where someone goes first where guests are not allowed, and they can ask from there', function () {
        $this->project->update(['allows_guests' => false]);
        $newcomer = aGuestIn($this->project);

        $this->as($newcomer, $this->project)->get(route('projects.show', $this->project))->assertRedirect(route('family.create', $this->project));
        ($this->ask)($newcomer)->assertSessionHasNoErrors();

        expect($this->family->joinRequests()->where('user_id', $newcomer->id)->exists())->toBeTrue();
    });
});

describe('Asking and answering', function () {
    it('tells the family once, however many times someone cancels and asks again', function () {
        foreach (range(1, 3) as $time) {
            ($this->ask)()->assertSessionHasNoErrors();
            $this->as($this->guest, $this->project)->delete(route('family.requests.destroy', FamilyJoinRequest::sole()));
        }
        ($this->ask)()->assertSessionHasNoErrors();

        expect(Notification::where('type', NotificationType::FAMILY_REQUESTED)->where('target_id', $this->member->id)->count())->toBe(1)
            ->and(FamilyJoinRequest::sole()->user_id)->toBe($this->guest->id);
        Notifications::assertSentToTimes($this->member, FamilyRequestNotification::class, 1);
    });

    it('tells them again once the quiet hours pass, and of another family at once', function () {
        $other = aFamily($this->project, $this->type, ['name' => 'Silva']);
        $relative = aMemberOf($other);

        ($this->ask)();
        $this->as($this->guest, $this->project)->delete(route('family.requests.destroy', FamilyJoinRequest::sole()));
        ($this->ask)(null, $other);
        expect(toldOf($relative, NotificationType::FAMILY_REQUESTED))->toBeTrue();
        $this->as($this->guest, $this->project)->delete(route('family.requests.destroy', FamilyJoinRequest::sole()));

        $this->travel(config('limits.asked_again_quiet_hours') + 1)->hours();
        ($this->ask)();

        expect(Notification::where('type', NotificationType::FAMILY_REQUESTED)->where('target_id', $this->member->id)->count())->toBe(2);
    });

    it('drops the email of a request taken back before it leaves, rather than failing', function () {
        $queued = new Illuminate\Notifications\SendQueuedNotifications(
            collect([$this->member]),
            new FamilyRequestNotification(new FamilyJoinRequest()),
        );

        expect($queued->deleteWhenMissingModels)->toBeTrue();
    });

    it('tells every member of the family, in the app and by email', function () {
        ($this->ask)();

        expect(toldOf($this->member, NotificationType::FAMILY_REQUESTED))->toBeTrue();
        Notifications::assertSentTo($this->member, FamilyRequestNotification::class);
    });

    it('lets any member accept, and they are in', function () {
        ($this->ask)();
        $joinRequest = FamilyJoinRequest::sole();

        $this->as($this->member, $this->project)->put(route('family.requests.accept', $joinRequest))->assertSessionHasNoErrors();

        expect($this->guest->fresh()->familyIn($this->project)->id)->toBe($this->family->id)
            ->and(FamilyJoinRequest::count())->toBe(0)
            ->and(toldOf($this->guest, NotificationType::ADDED_TO_FAMILY))->toBeTrue();
    });

    it('is for the family alone to answer: not other people, nor admins', function () {
        ($this->ask)();
        $joinRequest = FamilyJoinRequest::sole();

        expect($this->as($this->admin, $this->project)->put(route('family.requests.accept', $joinRequest)))->toBeDenied()
            ->and($this->as($this->guest, $this->project)->put(route('family.requests.accept', $joinRequest)))->toBeDenied()
            ->and($this->as(aMemberOf(aFamily($this->project, $this->type)), $this->project)->put(route('family.requests.decline', $joinRequest)))->toBeDenied();
    });

    it('takes one open request at a time, which they can cancel', function () {
        $other = aFamily($this->project, $this->type);
        aMemberOf($other);
        ($this->ask)();
        ($this->ask)(null, $other)->assertSessionHasErrors('family');

        $this->as($this->guest, $this->project)->delete(route('family.requests.destroy', FamilyJoinRequest::sole()))->assertSessionHasNoErrors();
        ($this->ask)(null, $other)->assertSessionHasNoErrors();
    });

    it('closes what they were asking when they get a family another way', function () {
        ($this->ask)();

        $this->guest->membershipIn($this->project)->update(['family_id' => aFamily($this->project, $this->type)->id]);

        expect(FamilyJoinRequest::count())->toBe(0);
    });
});

describe('A decline', function () {
    beforeEach(function () {
        ($this->ask)();
        $this->joinRequest = FamilyJoinRequest::sole();
        $this->as($this->member, $this->project)->put(route('family.requests.decline', $this->joinRequest))->assertSessionHasNoErrors();
    });

    it('stands for good, however long it has been', function () {
        $this->travel(5)->years();

        ($this->ask)()->assertSessionHasErrors('family');
        expect(toldOf($this->guest, NotificationType::FAMILY_DECLINED))->toBeTrue();
    });

    it('leaves them free to ask another family', function () {
        $other = aFamily($this->project, $this->type);
        aMemberOf($other);

        ($this->ask)(null, $other)->assertSessionHasNoErrors();
    });

    it('is lifted only by someone in the family', function () {
        expect($this->as($this->admin, $this->project)->put(route('family.requests.undecline', $this->joinRequest)))->toBeDenied()
            ->and($this->as($this->guest, $this->project)->delete(route('family.requests.destroy', $this->joinRequest)))->toBeDenied();

        $this->as($this->member, $this->project)->put(route('family.requests.undecline', $this->joinRequest))->assertSessionHasNoErrors();
        ($this->ask)()->assertSessionHasNoErrors();
    });

    it('stands against an admin\'s invitation too, while a member\'s invitation lifts it', function () {
        $this->as($this->admin, $this->project)->post(route('manage.invitations.store'), ['email' => $this->guest->email, 'family_id' => $this->family->id])
            ->assertSessionHasErrors('email');
        expect($this->guest->fresh()->familyIn($this->project))->toBeNull();

        app(InvitationService::class)->invite($this->project, $this->member, ['email' => $this->guest->email], $this->family, asAdmin: false);
        expect($this->guest->fresh()->familyIn($this->project)->id)->toBe($this->family->id);
    });
});

describe('Admins and a decline', function () {
    it('cannot move someone into a family that declined them', function () {
        FamilyJoinRequest::create(['project_id' => $this->project->id, 'family_id' => $this->family->id, 'user_id' => $this->guest->id, 'declined_at' => now()]);

        $this->as($this->admin, $this->project)
            ->put(route('manage.people.update', $this->guest), ['family_id' => $this->family->id, 'is_admin' => false])
            ->assertSessionHasErrors('family_id');

        expect($this->guest->familyIn($this->project))->toBeNull();
    });

    it('can still change the role of someone staying in the family', function () {
        FamilyJoinRequest::create(['project_id' => $this->project->id, 'family_id' => $this->family->id, 'user_id' => $this->member->id, 'declined_at' => now()]);

        $this->as($this->admin, $this->project)
            ->put(route('manage.people.update', $this->member), ['family_id' => $this->family->id, 'is_admin' => true])
            ->assertSessionHasNoErrors();
    });

    it('brings back an archived family by moving someone into it', function () {
        $archived = aFamily($this->project, $this->type, ['name' => 'Gone']);
        $archived->delete();

        $this->as($this->admin, $this->project)
            ->put(route('manage.people.update', $this->guest), ['family_id' => $archived->id, 'is_admin' => false])
            ->assertSessionHasNoErrors();

        expect($archived->fresh()->trashed())->toBeFalse()
            ->and($this->guest->fresh()->familyIn($this->project)->id)->toBe($archived->id);
    });

    it('stays within a Limited project\'s families when bringing one back', function () {
        $project = anUnapprovedProject(aUser());
        $admin = $project->admins()->first();
        $type = aUnitType($project);
        $guest = aGuestIn($project);
        $archived = aFamily($project, $type);
        $archived->delete();
        aFamily($project, $type);
        config(['limits.unapproved.families' => 1]);

        $this->as($admin, $project)
            ->put(route('manage.people.update', $guest), ['family_id' => $archived->id, 'is_admin' => false])
            ->assertSessionHasErrors('family_id');

        expect($archived->fresh()->trashed())->toBeTrue();
    });
});

describe('A family is shown while someone is in it', function () {
    it('comes back however someone lands in it', function () {
        $archived = aFamily($this->project, $this->type, ['name' => 'Gone']);
        $archived->delete();

        $this->project->memberships()->create(['user_id' => aUser()->id, 'family_id' => $archived->id]);

        expect($archived->fresh()->trashed())->toBeFalse();
    });
});

describe('Removing a family', function () {
    it('withdraws the invitations into it, so nobody lands in an archived family', function () {
        $invitation = app(InvitationService::class)->invite($this->project, $this->admin, ['email' => 'new@example.com'], $this->family, false);

        $this->as($this->admin, $this->project)->delete(route('manage.families.destroy', $this->family));

        expect($this->family->fresh()->trashed())->toBeTrue()
            ->and(Invitation::whereKey($invitation->id)->exists())->toBeFalse();
    });

    it('stays archived when the people limit refuses an invitation back into it', function () {
        $small = anUnapprovedProject(aUser());
        $family = aFamily($small, aUnitType($small));
        $family->delete();
        config(['limits.unapproved.members' => 0]);

        expect(fn () => app(InvitationService::class)->invite($small, $small->admins()->first(), ['email' => 'new@example.com'], $family, false))
            ->toThrow(\Illuminate\Validation\ValidationException::class)
            ->and($family->fresh()->trashed())->toBeTrue();
    });
});

describe('Leaving a family', function () {
    it('leaves them a guest where guests are allowed', function () {
        aMemberOf($this->family);

        $this->as($this->member, $this->project)->delete(route('family.leave'))->assertSessionHasNoErrors();

        expect($this->member->fresh()->familyIn($this->project))->toBeNull()
            ->and($this->member->fresh()->membershipIn($this->project))->not->toBeNull();
    });

    it('is refused where guests are not allowed: they ask to join the right one instead', function () {
        $this->project->update(['allows_guests' => false]);

        $this->as($this->member, $this->project)->delete(route('family.leave'))->assertSessionHasErrors('family');
        expect($this->member->fresh()->familyIn($this->project)->id)->toBe($this->family->id);
    });

    it('moves someone in the wrong family in one step, staying in theirs until accepted', function () {
        $this->project->update(['allows_guests' => false]);
        $right = aFamily($this->project, $this->type);
        $other = aMemberOf($right);
        aMemberOf($this->family);

        ($this->ask)($this->member, $right)->assertSessionHasNoErrors();
        expect($this->member->fresh()->familyIn($this->project)->id)->toBe($this->family->id);

        $this->as($other, $this->project)->put(route('family.requests.accept', FamilyJoinRequest::sole()))->assertSessionHasNoErrors();
        expect($this->member->fresh()->familyIn($this->project)->id)->toBe($right->id);
    });

    it('lets them create the right one at once, from the wrong one', function () {
        $this->project->update(['allows_guests' => false]);
        aMemberOf($this->family);

        $this->as($this->member, $this->project)->post(route('families.store'), ['name' => 'Rodríguez', 'unit_type_id' => $this->type->id])->assertSessionHasNoErrors();

        expect($this->member->fresh()->familyIn($this->project)->name)->toBe('Rodríguez');
    });
});

describe('A family with nobody left', function () {
    it('is archived when its last member leaves, and its admins are told', function () {
        $this->as($this->member, $this->project)->delete(route('family.leave'));

        expect(Family::find($this->family->id))->toBeNull()
            ->and(Family::withTrashed()->find($this->family->id))->not->toBeNull()
            ->and(toldOf($this->admin, NotificationType::FAMILY_DISCARDED))->toBeTrue();
    });

    it('is archived when they leave the project, or move to another family', function () {
        $this->member->membershipIn($this->project)->delete();
        expect(Family::find($this->family->id))->toBeNull();

        $other = aFamily($this->project, $this->type);
        $person = aMemberOf($other);
        $person->membershipIn($this->project)->update(['family_id' => aFamily($this->project, $this->type)->id]);
        expect(Family::find($other->id))->toBeNull();
    });

    it('goes with the last member\'s account', function () {
        app(App\Services\AccountService::class)->delete($this->member);

        expect(Family::find($this->family->id))->toBeNull();
    });

    it('stays while someone else is in it', function () {
        aMemberOf($this->family);

        $this->as($this->member, $this->project)->delete(route('family.leave'));

        expect(Family::find($this->family->id))->not->toBeNull();
    });

    it('stays while someone is invited, and goes when that invitation is revoked', function () {
        app(InvitationService::class)->invite($this->project, $this->admin, ['email' => 'new@example.com'], $this->family, asAdmin: false);

        $this->as($this->member, $this->project)->delete(route('family.leave'));
        expect(Family::find($this->family->id))->not->toBeNull();

        $this->as($this->admin, $this->project)->delete(route('manage.invitations.destroy', Invitation::sole()));
        expect(Family::find($this->family->id))->toBeNull();
    });

    it('goes when the invitation expires', function () {
        app(InvitationService::class)->invite($this->project, $this->admin, ['email' => 'new@example.com'], $this->family, asAdmin: false);
        $this->as($this->member, $this->project)->delete(route('family.leave'));

        $this->travel(config('limits.invitation_days') + 1)->days();
        (new DiscardFamilyIfEmpty($this->family->id))->handle(app(App\Services\FamilyService::class));

        expect(Family::find($this->family->id))->toBeNull();
    });

    it('stays if it received its unit', function () {
        Unit::factory()->create(['project_id' => $this->project->id, 'unit_type_id' => $this->type->id, 'family_id' => $this->family->id]);

        $this->as($this->member, $this->project)->delete(route('family.leave'));

        expect(Family::find($this->family->id))->not->toBeNull();
    });

    it('closes the requests waiting for it, and tells whoever asked', function () {
        ($this->ask)();

        $this->as($this->member, $this->project)->delete(route('family.leave'));

        expect(FamilyJoinRequest::count())->toBe(0)
            ->and(toldOf($this->guest, NotificationType::FAMILY_DISCARDED))->toBeTrue();
    });

    it('comes back, ranking and all, with an invitation or a guest added', function () {
        $unit = Unit::factory()->create(['project_id' => $this->project->id, 'unit_type_id' => $this->type->id]);
        $this->family->preferences()->attach($unit->id, ['rank' => 1]);
        $this->as($this->member, $this->project)->delete(route('family.leave'));

        $this->as($this->admin, $this->project)->post(route('manage.invitations.store'), ['email' => $this->guest->email, 'family_id' => $this->family->id])
            ->assertSessionHasNoErrors();

        $family = Family::find($this->family->id);
        expect($family)->not->toBeNull()
            ->and($this->guest->fresh()->familyIn($this->project)->id)->toBe($family->id)
            ->and($family->preferences()->pluck('units.id')->all())->toBe([$unit->id]);
    });

    it('is shown to admins, and not restored empty', function () {
        $this->as($this->member, $this->project)->delete(route('family.leave'));

        $archive = $this->as($this->admin, $this->project)->get(route('families.index', ['archived' => 1]))->viewData('page')['props']['archive'];
        expect($archive['items'][0]['title'])->toBe('Pereira');

        $this->as($this->admin, $this->project)->put(route('manage.archive.restore', ['kind' => 'families', 'id' => $this->family->id]))->assertNotFound();
    });
});

describe('Admins', function () {
    it('add a family with someone invited into it, all at once', function () {
        $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
            'name' => 'García', 'unit_type_id' => $this->type->id, 'email' => 'garcia@example.com',
        ])->assertSessionHasNoErrors();

        expect(Invitation::where('email', 'garcia@example.com')->sole()->family->name)->toBe('García');
    });

    it('cannot add a family with nobody', function () {
        $this->as($this->admin, $this->project)->post(route('manage.families.store'), ['name' => 'García', 'unit_type_id' => $this->type->id])
            ->assertSessionHasErrors('email');

        expect($this->project->families()->where('name', 'García')->exists())->toBeFalse();
    });

    it('are warned on the lottery page of families with only an invitation', function () {
        $family = aFamily($this->project, $this->type, ['name' => 'Silva']);
        app(InvitationService::class)->invite($this->project, $this->admin, ['email' => 'silva@example.com'], $family, asAdmin: false);

        $props = $this->as($this->admin, $this->project)->get(route('lottery.show'))->viewData('page')['props'];
        expect($props['admin']['families_without_people'])->toContain('Silva');
    });
});

describe('An admin moving or removing someone', function () {
    function runningLottery($project): void
    {
        $project->lotteryRuns()->create(['uuid' => (string) Str::uuid(), 'status' => App\Enums\LotteryRunStatus::RUNNING, 'solver' => 'glpk', 'started_at' => now()]);
    }

    it('is refused while a lottery runs', function () {
        runningLottery($this->project);

        $this->as($this->admin, $this->project)->put(route('manage.people.update', $this->member), ['family_id' => null, 'is_admin' => false])
            ->assertSessionHasErrors('lottery');
        $this->as($this->admin, $this->project)->delete(route('manage.people.destroy', $this->member))
            ->assertSessionHasErrors('lottery');
    });

    it('holds whoever would leave the project while a lottery runs, and says why', function () {
        runningLottery($this->project);

        $this->as($this->member, $this->project)->delete(route('membership.leave'))->assertSessionHasErrors('lottery');

        expect($this->member->fresh()->familyIn($this->project)?->id)->toBe($this->family->id);
    });

    it('lets them leave the project once it ended', function () {
        $run = aLotteryInProgress($this->project);
        $run->update(['status' => App\Enums\LotteryRunStatus::COMPLETED]);

        $this->as($this->member, $this->project)->delete(route('membership.leave'))->assertSessionHasNoErrors();

        expect($this->member->fresh()->canAccess($this->project))->toBeFalse();
    });

    it('still changes a role while a lottery runs', function () {
        runningLottery($this->project);

        $this->as($this->admin, $this->project)
            ->put(route('manage.people.update', $this->member), ['family_id' => $this->family->id, 'is_admin' => true])
            ->assertSessionHasNoErrors();
    });

    it('refuses removing the only admin, who stays', function () {
        $project = aProject();
        $only = anAdminOf($project);

        $this->as($only, $project)->delete(route('manage.people.destroy', $only))
            ->assertSessionHasErrors(['member' => __('validation.last_admin')]);

        expect($only->fresh()->manages($project))->toBeTrue();
    });

    it('removes an admin while another is left', function () {
        $project = aProject();
        $first = anAdminOf($project);
        $second = anAdminOf($project);

        $this->as($first, $project)->delete(route('manage.people.destroy', $second))->assertSessionHasNoErrors();

        expect($second->fresh()->canAccess($project))->toBeFalse();
    });

    it('keeps an old admin invitation from bringing someone back who was removed', function () {
        $invitation = app(InvitationService::class)->invite($this->project, $this->admin, ['email' => $this->member->email], null, true);

        $this->as($this->admin, $this->project)->delete(route('manage.people.destroy', $this->member))->assertSessionHasNoErrors();

        expect(Invitation::whereKey($invitation->id)->exists())->toBeFalse();
    });

    it('withdraws what someone demoted was invited to be', function () {
        $other = anAdminOf($this->project);
        Invitation::create(['project_id' => $this->project->id, 'is_admin' => true, 'email' => $other->email, 'token_hash' => 'x', 'invited_by' => $this->admin->id, 'expires_at' => now()->addDay()]);

        $this->as($this->admin, $this->project)->put(route('manage.people.update', $other), ['family_id' => null, 'is_admin' => false])->assertSessionHasNoErrors();

        expect(Invitation::where('email', $other->email)->exists())->toBeFalse();
    });

    it('tells whoever was moved into a family, and withdraws the invitation into another', function () {
        $other = aFamily($this->project, $this->type, ['name' => 'Other']);
        Invitation::create(['project_id' => $this->project->id, 'family_id' => $other->id, 'email' => $this->guest->email, 'token_hash' => 'y', 'invited_by' => $this->admin->id, 'expires_at' => now()->addDay()]);

        $this->as($this->admin, $this->project)->put(route('manage.people.update', $this->guest), ['family_id' => $this->family->id, 'is_admin' => false])->assertSessionHasNoErrors();

        expect(toldOf($this->guest, NotificationType::ADDED_TO_FAMILY))->toBeTrue()
            ->and(Invitation::where('email', $this->guest->email)->exists())->toBeFalse();
    });

    it('refuses a family that is not a plain id', function ($given) {
        $this->as($this->admin, $this->project)->put(route('manage.people.update', $this->guest), ['family_id' => $given, 'is_admin' => false])
            ->assertSessionHasErrors('family_id');
    })->with([['1abc'], [[1]]]);
});

describe('A family emptied while the lottery held it', function () {
    it('goes when the run fails', function () {
        $empty = aFamily($this->project, $this->type, ['name' => 'Emptied']);
        $run = $this->project->lotteryRuns()->create(['uuid' => (string) Str::uuid(), 'status' => App\Enums\LotteryRunStatus::RUNNING, 'solver' => 'glpk', 'started_at' => now()]);

        app(App\Services\LotteryService::class)->fail($run, 'It broke');

        expect($empty->fresh()->trashed())->toBeTrue();
    });
});

it('lets someone leave an archived project, even if their family empties', function () {
    $this->project->delete();

    $this->member->memberships()->where('project_id', $this->project->id)->first()->delete();

    expect($this->family->fresh()->trashed())->toBeTrue();
});

it('offers no chat to someone who has yet to find their family, where guests are not allowed', function () {
    $this->project->update(['allows_guests' => false]);

    $shared = $this->as($this->guest, $this->project)->get(route('family.create', $this->project))->inertiaProps('project');

    expect($shared['needs_family'])->toBeTrue()
        ->and($shared['welcome'])->toBeNull()
        ->and($shared['can']['chat'])->toBeFalse();
});
