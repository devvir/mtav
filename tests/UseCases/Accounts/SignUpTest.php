<?php

use App\Models\User;
use App\Notifications\AccountExistsNotice;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Anyone signs up; whether an address already has an account is nobody's business. The answer
 * is the same either way, an email sent: the link confirming a new account, or, to an account
 * that exists, where to sign in. Nothing is opened on an address that has one.
 */

beforeEach(fn () => Notification::fake());

function signUp($test, string $email = 'ana@example.com')
{
    return $test->post(route('register'), [
        'firstname' => 'Ana', 'email' => $email, 'password' => 'a-Strong-pass-123', 'password_confirmation' => 'a-Strong-pass-123',
    ]);
}

it('opens an account and sends the link that confirms it, followed once signed in', function () {
    signUp($this)->assertRedirect(route('login'))
        ->assertSessionHas('info', __('flash.signed_up', ['email' => 'ana@example.com']));

    $ana = User::firstWhere('email', 'ana@example.com');
    expect($ana)->not->toBeNull()->hasVerifiedEmail()->toBeFalse();
    Notification::assertSentTo($ana, VerifyEmailNotification::class);
    $this->assertGuest();

    // The link leads to signing in, and back to it
    $link = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $ana->id, 'hash' => sha1($ana->email)]);
    $this->get($link)->assertRedirect(route('login'));
    $this->post(route('login'), ['email' => 'ana@example.com', 'password' => 'a-Strong-pass-123'])->assertRedirect($link);
    $this->get($link);

    expect($ana->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('answers the same for an address that has an account, telling its owner by email alone', function () {
    $ana = aUser(['email' => 'ana@example.com', 'firstname' => 'Ana', 'password' => 'Her-own-pass-1']);

    signUp($this, 'Ana@Example.com')->assertRedirect(route('login'))
        ->assertSessionHas('info', __('flash.signed_up', ['email' => 'ana@example.com']))
        ->assertSessionHasNoErrors();

    expect(User::where('email', 'ana@example.com')->count())->toBe(1)
        ->and(Hash::check('Her-own-pass-1', $ana->fresh()->password))->toBeTrue();
    Notification::assertSentTo($ana, AccountExistsNotice::class);
    Notification::assertNotSentTo($ana, VerifyEmailNotification::class);
    $this->assertGuest();
});

it('tells the owner once an hour at most, however many times someone tries', function () {
    $ana = aUser(['email' => 'ana@example.com']);

    signUp($this);
    signUp($this);

    Notification::assertSentToTimes($ana, AccountExistsNotice::class, 1);

    $this->travel(61)->minutes();
    signUp($this);
    Notification::assertSentToTimes($ana, AccountExistsNotice::class, 2);
});

it('writes to the owner where to sign in', function () {
    $ana = aUser(['email' => 'ana@example.com', 'firstname' => 'Ana']);

    $html = (string) (new AccountExistsNotice())->toMail($ana)->render();

    expect($html)->toContain('href="' . route('login') . '"')->toContain(__('emails.account_exists_forgot'));
});
