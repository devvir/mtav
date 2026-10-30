<?php

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;

/**
 * A page left open too long sends a form whose token expired: the person lands back on
 * it, told to try again (the reload carries a fresh token), never on a bare error.
 */
it('sends someone back to try again when their page was open too long', function () {
    Route::middleware('web')->post('/_expired', fn () => throw new TokenMismatchException());

    $this->as(aUser())
        ->from(route('profile.edit'))
        ->post('/_expired')
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('error', __('flash.page_expired'));
});
