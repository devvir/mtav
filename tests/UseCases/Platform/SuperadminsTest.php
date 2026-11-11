<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SupportInvitationNotification;
use App\Models\SupportInvitation;

/**
 * The superadmin role, a support account: invited apart, on an address with no account yet, so
 * it is never anyone's own account. It takes no place in a project: it reaches every one.
 */

beforeEach(fn () => $this->withPasswordConfirmed());

it('invites a support account to an address with no account, which becomes a superadmin in no project', function () {
    Notification::fake();

    $this->as(aSuperadmin())->post(route('admin.superadmins.store'), ['email' => 'New.Support@Example.com'])->assertSessionHasNoErrors();

    $token = null;
    Notification::assertSentOnDemand(SupportInvitationNotification::class, function ($notice) use (&$token) {
        $token = $notice->token;

        return true;
    });
    auth()->logout();
    $this->get(route('support-invitation.show', $token))->assertOk()->assertInertia(fn ($page) => $page->component('Auth/Invitation')->where('support', true));
    $this->post(route('support-invitation.accept', $token), ['firstname' => 'Sol', 'password' => 'the-Support-pass-1', 'password_confirmation' => 'the-Support-pass-1'])
        ->assertRedirect(route('admin.projects.index'));

    $support = User::firstWhere('email', 'new.support@example.com');
    expect($support->isSuperadmin())->toBeTrue()
        ->and($support->hasVerifiedEmail())->toBeTrue()
        ->and($support->memberships()->count())->toBe(0);
    $this->get(route('support-invitation.show', $token))->assertInertia(fn ($page) => $page->component('Auth/InvitationInvalid'));
});

it('refuses an address that already has an account: a support account is apart from anyone\'s own', function () {
    aUser(['email' => 'ana@example.com']);

    $this->as(aSuperadmin())
        ->post(route('admin.superadmins.store'), ['email' => 'ana@example.com'])
        ->assertSessionHasErrors(['email' => __('validation.support_email_taken')]);
    expect(SupportInvitation::count())->toBe(0);
});

it('never gives the role to an existing account', function () {
    $ana = aUser(['email' => 'ana@example.com']);

    $this->as(aSuperadmin())
        ->put(route('admin.users.superadmin', $ana), ['grant' => true])
        ->assertSessionHasErrors(['grant' => __('validation.support_by_invitation')]);
    expect($ana->fresh()->isSuperadmin())->toBeFalse();
});

it('keeps the invitations away from anyone else', function () {
    expect($this->as(aUser())->post(route('admin.superadmins.store'), ['email' => 'new@example.com']))->toBeDenied();
    expect(SupportInvitation::count())->toBe(0);
});

it('takes a support account into no project: invitations to its address look sent, and stay unanswered', function () {
    Notification::fake();
    $support = aSuperadmin();
    $project = aProject();

    $this->as(anAdminOf($project), $project)
        ->post(route('manage.invitations.store', $project), ['email' => $support->email, 'is_admin' => true])
        ->assertSessionHas('success', __('flash.invitation_sent', ['email' => $support->email]));

    Notification::assertNothingSent();
    expect($project->invitations()->pending()->where('email', $support->email)->exists())->toBeTrue()
        ->and($this->as($support)->get(route('projects.index'))->inertiaProps('invitations'))->toBe([])
        ->and(fn () => $project->memberships()->create(['user_id' => $support->id]))->toThrow(LogicException::class);
});

it('lets a support account into any project\'s chat and files, to try them, as nobody\'s member', function () {
    $project = aProject();
    $member = aMemberOf(aFamily($project));
    $support = aSuperadmin();

    $this->as($support, $project)->get(route('projects.show', $project))->assertInertia(fn ($page) => $page->where('project.can.chat', true)->where('project.can.upload', true));
    $this->as($support, $project)->postJson(route('chat.store', $project), ['people' => [$member->id]])->assertOk();
});

describe('Taking the role back', function () {
    beforeEach(function () {
        $this->superadmin = aSuperadmin();
        $this->other = User::factory()->superadmin()->create();
    });

    it('takes it from another superadmin', function () {
        $this->as($this->superadmin)
            ->put(route('admin.users.superadmin', $this->other), ['grant' => false])
            ->assertSessionHasNoErrors();

        expect($this->other->fresh()->isSuperadmin())->toBeFalse();
    });

    it('refuses taking it from oneself', function () {
        $this->as($this->superadmin)
            ->put(route('admin.users.superadmin', $this->superadmin), ['grant' => false])
            ->assertSessionHasErrors(['grant' => __('validation.cannot_revoke_self')]);

        expect($this->superadmin->fresh()->isSuperadmin())->toBeTrue();
    });

    it('is for superadmins alone, either way', function () {
        $ana = aUser();

        expect($this->as($ana)->put(route('admin.users.superadmin', $ana), ['grant' => true]))->toBeDenied()
            ->and($this->as($ana)->put(route('admin.users.superadmin', $this->other), ['grant' => false]))->toBeDenied();

        expect($ana->fresh()->isSuperadmin())->toBeFalse()
            ->and($this->other->fresh()->isSuperadmin())->toBeTrue();
    });
});

describe('The owner', function () {
    beforeEach(function () {
        $this->owner = aUser();
        $this->owner->forceFill(['is_owner' => true, 'is_superadmin' => true])->save();
        $this->other = aSuperadmin();
        $this->other->is($this->owner) && $this->other = User::factory()->superadmin()->create();
    });

    it('keeps the role, whoever asks', function () {
        $this->as($this->other)
            ->put(route('admin.users.superadmin', $this->owner), ['grant' => false])
            ->assertSessionHasErrors(['grant' => __('validation.owner_keeps_role')]);

        expect($this->owner->fresh()->isSuperadmin())->toBeTrue();
    });

    it('cannot be blocked or deleted, even without the role', function () {
        $this->owner->forceFill(['is_superadmin' => false])->save();

        $this->as($this->other)->put(route('admin.users.block', $this->owner), ['block' => true])->assertSessionHasErrors('account');
        $this->as($this->other)->delete(route('admin.users.destroy', $this->owner))->assertSessionHasErrors('account');

        expect($this->owner->fresh())->not->toBeNull()->isBlocked()->toBeFalse();
    });

    it('is marked on the page, which offers no removing them', function () {
        $this->as($this->other)->get(route('admin.superadmins.index'))
            ->assertInertia(fn ($page) => $page->where('superadmins', fn ($people) => collect($people)->firstWhere('id', $this->owner->id)['is_owner'] === true
                && collect($people)->firstWhere('id', $this->other->id)['is_owner'] === false));
    });

    it('leaves other superadmins removable', function () {
        $this->as($this->owner)
            ->put(route('admin.users.superadmin', $this->other), ['grant' => false])
            ->assertSessionHasNoErrors();

        expect($this->other->fresh()->isSuperadmin())->toBeFalse();
    });
});

describe('A support invitation', function () {
    beforeEach(function () {
        Notification::fake();
        $this->inviter = User::factory()->superadmin()->create();
        $this->as($this->inviter)->post(route('admin.superadmins.store'), ['email' => 'new.support@example.com'])->assertSessionHasNoErrors();
        Notification::assertSentOnDemand(SupportInvitationNotification::class, function ($notice) {
            $this->token = $notice->token;

            return true;
        });
        $this->invitation = SupportInvitation::sole();
    });

    /** Whether its link still opens an account */
    function supportLinkWorks($test): bool
    {
        auth()->logout();
        $works = $test->get(route('support-invitation.show', $test->token))->viewData('page')['component'] === 'Auth/Invitation';
        $accepted = $test->post(route('support-invitation.accept', $test->token), ['firstname' => 'Sol', 'password' => 'the-Support-pass-1', 'password_confirmation' => 'the-Support-pass-1']);

        expect($works)->toBe($accepted->isRedirect());

        return $works && User::where('email', 'new.support@example.com')->where('is_superadmin', true)->exists();
    }

    it('is listed while unanswered, with who sent it, and withdrawn from there', function () {
        $listed = $this->as(aSuperadmin())->get(route('admin.superadmins.index'))->inertiaProps('invitations');
        expect($listed)->toHaveCount(1)
            ->and($listed[0])->email->toBe('new.support@example.com')->inviter->toBe($this->inviter->name);

        $this->as(aSuperadmin())->delete(route('admin.superadmins.invitations.destroy', $this->invitation))->assertSessionHasNoErrors();

        expect(SupportInvitation::count())->toBe(0)
            ->and(supportLinkWorks($this))->toBeFalse();
    });

    it('works while whoever sent it runs the platform', function () {
        expect(supportLinkWorks($this))->toBeTrue();
    });

    it('stops working when whoever sent it loses the role', function () {
        $this->as(User::factory()->superadmin()->create())->put(route('admin.users.superadmin', $this->inviter), ['grant' => false])->assertSessionHasNoErrors();

        expect(SupportInvitation::count())->toBe(0)
            ->and(supportLinkWorks($this))->toBeFalse();
    });

    it('stops working when whoever sent it is blocked, or no longer a superadmin however that came', function (string $how) {
        $how === 'blocked'
            ? $this->inviter->forceFill(['is_superadmin' => false, 'blocked_at' => now()])->save()
            : $this->inviter->forceFill(['is_superadmin' => false])->save();

        expect(supportLinkWorks($this))->toBeFalse();
    })->with(['blocked', 'no longer a superadmin']);

    it('stops working when whoever sent it is gone', function () {
        $this->inviter->delete();

        expect(supportLinkWorks($this))->toBeFalse();
    });

    it('is voided when whoever sent it is blocked', function () {
        $this->inviter->forceFill(['is_superadmin' => false])->save();

        app(App\Services\AccountService::class)->block($this->inviter);

        expect(SupportInvitation::count())->toBe(0);
    });

    it('cannot be withdrawn by anyone else', function () {
        expect($this->as(aUser())->delete(route('admin.superadmins.invitations.destroy', $this->invitation)))->toBeDenied();

        expect(SupportInvitation::count())->toBe(1);
    });
});

it('refuses an address too long to keep, saying so', function () {
    $this->as(aSuperadmin())->post(route('admin.superadmins.store'), ['email' => str_repeat('a', 250) . '@example.com'])
        ->assertSessionHasErrors('email');

    expect(SupportInvitation::count())->toBe(0);
});

describe('The command line', function () {
    it('makes an account in no project a superadmin', function () {
        $ana = aUser(['email' => 'ana@example.com']);

        $this->artisan('mtav:superadmin', ['email' => 'Ana@Example.com'])->assertSuccessful();

        expect($ana->fresh()->isSuperadmin())->toBeTrue();
    });

    it('refuses an account in a project, or asking to join one, saying what to do', function (string $where) {
        $project = aProject(['listed' => true]);
        $ana = aUser(['email' => 'ana@example.com']);
        $where === 'in a project'
            ? $project->memberships()->create(['user_id' => $ana->id, 'family_id' => aFamily($project)->id])
            : $project->joinRequests()->create(['user_id' => $ana->id]);

        $this->artisan('mtav:superadmin', ['email' => 'ana@example.com'])
            ->expectsOutputToContain('a superadmin takes no place in any')
            ->assertFailed();

        expect($ana->fresh()->isSuperadmin())->toBeFalse();
    })->with(['in a project', 'asking to join one']);

    it('takes the role away, and the support invitations they sent with it', function () {
        $ana = User::factory()->superadmin()->create(['email' => 'ana@example.com']);
        SupportInvitation::create(['email' => 'x@example.com', 'token_hash' => 'h', 'invited_by' => $ana->id, 'expires_at' => now()->addWeek()]);

        $this->artisan('mtav:superadmin', ['email' => 'ana@example.com', '--revoke' => true])->assertSuccessful();

        expect($ana->fresh()->isSuperadmin())->toBeFalse()
            ->and(SupportInvitation::count())->toBe(0);
    });
});
