<?php

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
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

it('refuses an address it has nothing at, such as a missing file, never failing on it', function () {
    expect($this->get('/storage/avatars/gone.webp')->status())->toBeIn([403, 404]);
    $this->get('/no/such/place')->assertNotFound();
});

it('says it on the page when a form was sent too often, or something changed under it', function (string $error, string $message) {
    Route::middleware('web')->post('/_refused', fn () => throw new $error());

    $this->as(aUser())
        ->from(route('profile.edit'))
        ->post('/_refused')
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('error', __($message));
})->with([
    'too often' => [ThrottleRequestsException::class, 'flash.too_many_attempts'],
    'changed'   => [ConflictHttpException::class, 'flash.changed_meanwhile'],
]);

it('shows a page of the app when a link was opened too often', function () {
    Route::middleware('web')->get('/_refused', fn () => throw new ThrottleRequestsException());

    $this->as(aUser())->get('/_refused')
        ->assertTooManyRequests()
        ->assertInertia(fn ($page) => $page->component('Error')->where('status', 429));
});

it('answers a program that reads JSON plainly', function () {
    Route::middleware('web')->post('/_refused', fn () => throw new ThrottleRequestsException());

    $this->as(aUser())->postJson('/_refused')->assertTooManyRequests();
});
