<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Superadmins close accounts: deleting one is bookkeeping (its email may sign up again);
 * blocking one keeps it, its email taken, so that it never joins again.
 */

beforeEach(function () {
    Notification::fake();
    $this->superadmin = aSuperadmin();
    $this->project = aProject();
    $this->family = aFamily($this->project);
    anAdminOf($this->project);
    $this->person = aMemberOf($this->family, ['email' => 'person@example.com', 'password' => 'a-Strong-pass-123']);
});

describe('Blocking', function () {
    it('takes them out of every project, keeps the account and its email', function () {
        $this->as($this->superadmin)->put(route('admin.users.block', $this->person), ['block' => true])->assertSessionHasNoErrors();

        $person = User::firstWhere('email', 'person@example.com');
        expect($person->isBlocked())->toBeTrue()
            ->and($person->memberships()->count())->toBe(0);
    });

    it('refuses them signing in', function () {
        $this->as($this->superadmin)->put(route('admin.users.block', $this->person), ['block' => true]);
        auth()->logout();

        $this->post(route('login'), ['email' => 'person@example.com', 'password' => 'a-Strong-pass-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('ends a session they already had', function () {
        $this->person->forceFill(['blocked_at' => now()])->save();

        $this->as($this->person->fresh())->get(route('projects.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    });

    it('keeps them from signing up again, or being invited', function () {
        $this->person->forceFill(['blocked_at' => now()])->save();
        auth()->logout();

        $this->post(route('register'), [
            'firstname' => 'Again', 'email' => 'person@example.com', 'password' => 'a-Strong-pass-123', 'password_confirmation' => 'a-Strong-pass-123',
        ])->assertSessionHasErrors('email');

        $this->as($this->superadmin, $this->project)->post(route('manage.invitations.store'), ['email' => 'person@example.com', 'family_id' => $this->family->id])
            ->assertSessionHasErrors('email');
    });

    it('can be undone, though they join projects anew', function () {
        $this->as($this->superadmin)->put(route('admin.users.block', $this->person), ['block' => true]);
        $this->as($this->superadmin)->put(route('admin.users.block', $this->person), ['block' => false]);

        expect($this->person->fresh()->isBlocked())->toBeFalse();
    });

    it('takes back what was waiting for them', function () {
        $this->project->invitations()->create(['email' => 'person@example.com', 'token_hash' => 'x', 'expires_at' => now()->addDay()]);

        $this->as($this->superadmin)->put(route('admin.users.block', $this->person), ['block' => true]);

        expect($this->project->invitations()->count())->toBe(0);
    });
});

describe('Deleting', function () {
    it('takes the account away, and they may sign up again', function () {
        $this->as($this->superadmin)->delete(route('admin.users.destroy', $this->person))->assertSessionHasNoErrors();

        expect(User::firstWhere('email', 'person@example.com'))->toBeNull();
        auth()->logout();

        $this->post(route('register'), [
            'firstname' => 'Again', 'email' => 'person@example.com', 'password' => 'a-Strong-pass-123', 'password_confirmation' => 'a-Strong-pass-123',
        ])->assertSessionHasNoErrors();
    });

    it('is refused for the only admin of a project, saying which', function () {
        $alone = aProject(['name' => 'Alone']);
        $admin = anAdminOf($alone);

        $this->as($this->superadmin)->delete(route('admin.users.destroy', $admin))->assertSessionHasErrors('account');
        expect(User::find($admin->id))->not->toBeNull();
    });
});

it('protects superadmins and oneself', function (string $how) {
    $other = aSuperadmin();
    $act = fn (User $target) => $how === 'block'
        ? $this->as($this->superadmin)->put(route('admin.users.block', $target), ['block' => true])
        : $this->as($this->superadmin)->delete(route('admin.users.destroy', $target));

    $act($other)->assertSessionHasErrors('account');
    $act($this->superadmin)->assertSessionHasErrors('account');

    expect($other->fresh()->isBlocked())->toBeFalse();
})->with(['block', 'delete']);

it('is for superadmins alone', function () {
    $admin = $this->project->admins()->first();

    expect($this->as($admin)->put(route('admin.users.block', $this->person), ['block' => true]))->toBeDenied();
    expect($this->as($admin)->delete(route('admin.users.destroy', $this->person)))->toBeDenied();
    expect($this->person->fresh()->isBlocked())->toBeFalse();
});
