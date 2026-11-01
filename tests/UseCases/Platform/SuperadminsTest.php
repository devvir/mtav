<?php

use App\Models\User;

/**
 * The superadmin role: given apart, to an account named by its exact email.
 */

it('gives the role to the account with that exact email', function () {
    $ana = aUser(['email' => 'ana@example.com']);

    $this->as(aSuperadmin())
        ->post(route('admin.superadmins.store'), ['email' => 'ana@example.com'])
        ->assertSessionHasNoErrors();

    expect($ana->fresh()->isSuperadmin())->toBeTrue();
});

it('says when no account has that email', function () {
    $this->as(aSuperadmin())
        ->post(route('admin.superadmins.store'), ['email' => 'nobody@example.com'])
        ->assertSessionHasErrors(['email' => __('validation.no_account_with_email')]);
});

it('keeps the role away from anyone else', function () {
    $ana = aUser(['email' => 'ana@example.com']);

    expect($this->as(aUser())->post(route('admin.superadmins.store'), ['email' => 'ana@example.com']))->toBeDenied();
    expect($ana->fresh()->isSuperadmin())->toBeFalse();
});

it('gives the role only to an account that confirmed its email and is not blocked', function (array $state) {
    $ana = aUser(['email' => 'ana@example.com']);
    $ana->forceFill($state)->save();

    $this->as(aSuperadmin())
        ->post(route('admin.superadmins.store'), ['email' => 'ana@example.com'])
        ->assertSessionHasErrors(['email' => __('validation.superadmin_unconfirmed')]);
    $this->as(aSuperadmin())
        ->put(route('admin.users.superadmin', $ana), ['grant' => true])
        ->assertSessionHasErrors(['grant' => __('validation.superadmin_unconfirmed')]);

    expect($ana->fresh()->isSuperadmin())->toBeFalse();
})->with([
    'unconfirmed' => [['email_verified_at' => null]],
    'blocked'     => [['blocked_at' => '2026-01-01 00:00:00']],
]);

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
