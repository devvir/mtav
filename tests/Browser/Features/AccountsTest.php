<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

/**
 * Accounts: signing up, in and out, passwords, email changes, appearance.
 */

it('explains what is wrong with a sign-up', function () {
    aUser(['email' => 'taken@example.com']);

    visit(route('register'))
        ->click('Create the account')
        ->assertSee('The first name field is required.')
        ->fill('firstname', 'Laura')
        ->fill('email', 'taken@example.com')
        ->fill('password', 'a-Strong-pass-123')
        ->fill('password_confirmation', 'something-else')
        ->click('Create the account')
        ->assertSee('The email has already been taken.')
        ->assertSee('The password field confirmation does not match.')
        ->assertNoSmoke();

    expect(User::count())->toBe(1);
});

it('asks to confirm the email before anything else', function () {
    $this->as(User::factory()->unverified()->create(['email' => 'new@example.com']));

    visit(route('projects.index'))
        ->assertSee('Confirm your email')
        ->assertSee('new@example.com');
});

it('signs in, refusing a wrong password', function () {
    aUser(['email' => 'ana@example.com', 'password' => 'the-Right-pass-1']);

    visit(route('login'))
        ->fill('email', 'ana@example.com')
        ->fill('password', 'a-wrong-one')
        ->click('[type="submit"]')
        ->assertSee('These credentials do not match our records.')
        ->fill('password', 'the-Right-pass-1')
        ->click('[type="submit"]')
        ->assertSee('Your projects')
        ->assertNoSmoke();
});

it('resets a forgotten password from the emailed link', function () {
    Notification::fake();
    $user = aUser(['email' => 'ana@example.com']);

    visit(route('password.request'))
        ->fill('email', 'ana@example.com')
        ->click('Send the link');

    visit(passwordResetLinkFor($user))
        ->fill('password', 'a-New-pass-123')
        ->fill('password_confirmation', 'a-New-pass-123')
        ->click('Save the password')
        ->fill('email', 'ana@example.com')
        ->fill('password', 'a-New-pass-123')
        ->click('[type="submit"]')
        ->assertSee('Your projects');
});

it('changes the password, checking the current one', function () {
    $user = aUser(['password' => 'the-Old-pass-1']);
    $this->as($user);

    visit(route('password.edit'))
        ->fill('current_password', 'not-it')
        ->fill('password', 'a-New-pass-123')
        ->fill('password_confirmation', 'a-New-pass-123')
        ->click('Change the password')
        ->assertSee('The password is incorrect.')
        ->fill('current_password', 'the-Old-pass-1')
        ->fill('password', 'a-New-pass-123')
        ->fill('password_confirmation', 'a-New-pass-123')
        ->click('Change the password')
        ->assertSee('Your password was changed.');

    expect(Hash::check('a-New-pass-123', $user->fresh()->password))->toBeTrue();
});

it('changes the email only once the new address is confirmed', function () {
    Notification::fake();
    $user = aUser(['email' => 'old@example.com']);
    $this->as($user);

    visit(route('profile.edit'))
        ->fill('email', 'new@example.com')
        ->click('Save')
        ->assertSee('We sent a confirmation link to new@example.com');

    expect($user->fresh()->email)->toBe('old@example.com');

    visit(emailConfirmationLinkFor($user, 'new@example.com'))
        ->assertSee('Your email address was changed.');

    expect($user->fresh()->email)->toBe('new@example.com');
});

it('edits the profile', function () {
    $this->as(aUser(['firstname' => 'Ana']));

    visit(route('profile.edit'))
        ->fill('firstname', 'Anita')
        ->fill('about', 'Architect, mother of two.')
        ->click('Save')
        ->assertSee('Your profile was saved.')
        ->assertNoSmoke();
});

it('switches to dark mode and high contrast', function () {
    $this->as(aUser());

    $page = visit(route('appearance'))->click('Dark')->click('High contrast');

    expect($page->script('document.documentElement.className'))
        ->toContain('dark')
        ->toContain('theme-high-contrast');
});

it('signs out', function () {
    $this->as(aUser(['firstname' => 'Ana']));

    visit(route('projects.index'))
        ->click('[aria-label="Your account"]')
        ->click('Sign out')
        ->assertSee('Sign in');

    $this->assertGuest();
});
