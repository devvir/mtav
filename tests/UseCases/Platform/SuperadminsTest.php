<?php

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
