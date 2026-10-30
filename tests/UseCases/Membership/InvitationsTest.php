<?php

use App\Models\Invitation;
use App\Models\User;
use App\Notifications\InvitationNotification;
use Illuminate\Support\Facades\Notification;

/**
 * People join a project by invitation: admins invite into any family (or as admins),
 * members only into their own family. The emailed link creates the account, or adds
 * the project to an existing one.
 */

beforeEach(function () {
    Notification::fake();

    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->family = aFamily($this->project);
});

/**
 * Admins invite from the project admin area, members from the member app.
 */
function invite($test, User $by, array $data = [])
{
    $route = $by->manages($test->project) ? 'manage.invitations.store' : 'invitations.store';

    return $test->as($by, $test->project)->post(route($route), [
        'email'     => 'new.person@example.com',
        'firstname' => 'Ana',
        ...$data,
    ]);
}

/**
 * The link emailed for the latest invitation to new.person@example.com.
 */
function invitationLink(): string
{
    $url = null;

    Notification::assertSentOnDemand(InvitationNotification::class, function ($notification) use (&$url) {
        $url = route('invitation.show', $notification->token);

        return $notification->invitation->email === 'new.person@example.com';
    });

    return $url;
}

function invitedPerson(): ?User
{
    return User::firstWhere('email', 'new.person@example.com');
}

describe('An admin', function () {
    it('invites a person into a family of their project', function () {
        invite($this, $this->admin, ['family_id' => $this->family->id])->assertSessionHasNoErrors();

        expect(Invitation::sole())->family_id->toBe($this->family->id)->is_admin->toBeFalse()
            ->and(invitationLink())->toContain('/invitations/');
    });

    it('invites a fellow admin', function () {
        invite($this, $this->admin, ['is_admin' => true])->assertSessionHasNoErrors();

        expect(Invitation::sole()->is_admin)->toBeTrue();
    });

    it('cannot invite into a family of another project', function () {
        $foreignFamily = aFamily(aProject());

        invite($this, $this->admin, ['family_id' => $foreignFamily->id])->assertSessionHasErrors('family_id');

        expect(Invitation::count())->toBe(0);
    });

    it('cannot invite someone already in the family', function () {
        aMemberOf($this->family, ['email' => 'new.person@example.com']);

        invite($this, $this->admin, ['family_id' => $this->family->id])->assertSessionHasErrors('email');
    });

    it('revokes a pending invitation, whose link then stops working', function () {
        invite($this, $this->admin, ['family_id' => $this->family->id]);
        $link = invitationLink();

        $this->as($this->admin, $this->project)->delete(route('manage.invitations.destroy', Invitation::sole()));

        auth()->logout();
        $this->get($link)->assertInertia(fn ($page) => $page->component('Auth/InvitationInvalid'));
    });
});

describe('A member', function () {
    beforeEach(fn () => $this->member = aMemberOf($this->family));

    it('invites a person into their own family', function () {
        invite($this, $this->member)->assertSessionHasNoErrors();

        expect(Invitation::sole()->family_id)->toBe($this->family->id);
    });

    it('cannot invite a person into another family', function () {
        $otherFamily = aFamily($this->project);

        invite($this, $this->member, ['family_id' => $otherFamily->id]);

        expect(Invitation::where('family_id', $otherFamily->id)->exists())->toBeFalse();
    });

    it('cannot invite admins', function () {
        invite($this, $this->member, ['is_admin' => true]);

        expect(Invitation::where('is_admin', true)->exists())->toBeFalse();
    });
});

describe('An invited person', function () {
    it('without an account, joins by choosing a password', function () {
        invite($this, $this->admin, ['family_id' => $this->family->id]);
        $link = invitationLink();
        auth()->logout();

        $this->get($link)->assertOk();
        $this->post($link, [
            'firstname'             => 'Ana',
            'password'              => 'a-Strong-pass-123',
            'password_confirmation' => 'a-Strong-pass-123',
        ])->assertRedirect(route('projects.show', $this->project));

        expect(invitedPerson())->not->toBeNull()
            ->hasVerifiedEmail()->toBeTrue()
            ->and(invitedPerson()->familyIn($this->project)?->id)->toBe($this->family->id);

        auth()->logout();
        $this->post(route('login'), ['email' => 'new.person@example.com', 'password' => 'a-Strong-pass-123']);
        $this->assertAuthenticatedAs(invitedPerson());
    });

    it('with an account, joins after signing in', function () {
        $existing = aUser(['email' => 'new.person@example.com']);
        invite($this, $this->admin, ['family_id' => $this->family->id]);
        $link = invitationLink();
        auth()->logout();

        $this->get($link)->assertRedirect(route('login'));

        $this->as($existing)->post($link)->assertRedirect(route('projects.show', $this->project));

        expect($existing->fresh()->familyIn($this->project)?->id)->toBe($this->family->id);
    });

    it('with an account, accepts from their projects page', function () {
        $existing = aUser(['email' => 'new.person@example.com']);
        invite($this, $this->admin, ['family_id' => $this->family->id]);

        $listed = $this->as($existing)->get(route('projects.index'))->inertiaProps('invitations');
        expect($listed)->toHaveCount(1);

        $this->as($existing)->post(route('invitations.respond', Invitation::sole()), ['accept' => true]);

        expect($existing->fresh()->canAccess($this->project))->toBeTrue();
    });

    it('cannot accept an invitation meant for someone else', function () {
        invite($this, $this->admin, ['family_id' => $this->family->id]);
        $link = invitationLink();
        $intruder = aUser();

        expect($this->as($intruder)->post($link))->toBeDenied();

        expect($intruder->fresh()->canAccess($this->project))->toBeFalse();
    });

    it('cannot use an expired link', function () {
        invite($this, $this->admin, ['family_id' => $this->family->id]);
        $link = invitationLink();
        auth()->logout();

        $this->travel(config('limits.invitation_days') + 1)->days();

        $this->get($link)->assertInertia(fn ($page) => $page->component('Auth/InvitationInvalid'));
        expect($this->post($link, ['firstname' => 'Ana', 'password' => 'x', 'password_confirmation' => 'x']))->toBeDenied();
    });

    it('becomes visible to the project when joining', function () {
        invite($this, $this->admin, ['family_id' => $this->family->id]);
        $link = invitationLink();
        auth()->logout();

        $this->post($link, ['firstname' => 'Ana', 'password' => 'a-Strong-pass-123', 'password_confirmation' => 'a-Strong-pass-123']);

        $seen = $this->as($this->admin)->get(route('notifications.index'))->inertiaProps('notifications.data');
        expect(collect($seen)->pluck('title'))->toContain(__('notifications.member_joined_title'));
    });
});

it('never moves someone already in a family into another one', function () {
    $other = aFamily($this->project);
    invite($this, $this->admin, ['family_id' => $other->id]);
    $link = invitationLink();
    // Meanwhile, they joined the first family through another invitation
    $person = aMemberOf($this->family, ['email' => 'new.person@example.com']);

    $this->as($person)->post($link);

    expect($person->fresh()->familyIn($this->project)?->id)->toBe($this->family->id);
});

describe('An unapproved project', function () {
    it('invites only a limited number of people a day', function () {
        config()->set('limits.unapproved.invitations_per_day', 2);
        $project = anUnapprovedProject();
        $this->project = $project;
        $admin = $project->admins()->first();
        $family = aFamily($project);

        foreach (['a', 'b'] as $name) {
            invite($this, $admin, ['email' => "{$name}@example.com", 'family_id' => $family->id])->assertSessionHasNoErrors();
        }

        invite($this, $admin, ['email' => 'c@example.com', 'family_id' => $family->id])->assertSessionHasErrors('email');

        expect($project->invitations()->count())->toBe(2);
    });
});

it('tells someone who already has an account, in the app and in their language, where to join', function () {
    $existing = aUser(['email' => 'new.person@example.com', 'locale' => 'es_UY']);

    invite($this, $this->admin, ['is_admin' => true, 'family_id' => null])->assertSessionHasNoErrors();

    $notice = $this->as($existing)->get(route('notifications.index'))->inertiaProps('notifications.data.0');
    expect($notice['title'])->toBe('Te invitaron a un proyecto')
        ->and($notice['message'])->toContain($this->project->name)
        ->and($notice['url'])->toBe(route('projects.index', absolute: false));
});

it('writes each notification in the language of whoever reads it', function () {
    $spanish = aMemberOf(aFamily($this->project), ['locale' => 'es_UY']);
    $french = aMemberOf(aFamily($this->project), ['locale' => 'fr']);

    app(App\Services\LotteryService::class)->invalidate(tap(aLotteryInProgress($this->project))->update(['status' => App\Enums\LotteryRunStatus::COMPLETED]), $this->admin, 'A family was missing');

    $titleFor = fn ($user) => $this->as($user)->get(route('notifications.index'))->inertiaProps('notifications.data.0.title');
    expect($titleFor($spanish))->toBe(__('notifications.lottery_invalidated_title', [], 'es_UY'))
        ->and($titleFor($french))->toBe(__('notifications.lottery_invalidated_title', [], 'fr'))
        ->and(__('notifications.lottery_invalidated_title', [], 'fr'))->not->toBe(__('notifications.lottery_invalidated_title', [], 'es_UY'));
});

it('tells no one in the app when the person has no account yet (the email is theirs)', function () {
    invite($this, $this->admin, ['family_id' => $this->family->id])->assertSessionHasNoErrors();

    expect(App\Models\Notification::where('type', 'invitation_received')->count())->toBe(0);
});

it('never invites from the member app someone into no family', function () {
    $this->as($this->admin, $this->project)->post(route('invitations.store'), ['email' => 'new.person@example.com']);

    expect(Invitation::count())->toBe(0);
});

it('tells admins, on every page of the project, how many invitations nobody answered yet', function () {
    invite($this, $this->admin, ['email' => 'one@example.com', 'family_id' => $this->family->id])->assertSessionHasNoErrors();
    invite($this, $this->admin, ['email' => 'two@example.com', 'family_id' => $this->family->id])->assertSessionHasNoErrors();

    expect($this->as($this->admin, $this->project)->get(route('manage.overview'))->inertiaProps('project.pending_invitations'))->toBe(2)
        // Members are told nothing of it
        ->and($this->as(aMemberOf($this->family), $this->project)->get(route('projects.show'))->inertiaProps('project.pending_invitations'))->toBeNull();
});
