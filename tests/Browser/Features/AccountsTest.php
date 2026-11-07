<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\ChatService;
use Illuminate\Support\Facades\Notification;

/**
 * Accounts: signing up, in and out, the profile (details, password, appearance), email changes.
 */

it('explains what is wrong with a sign-up', function () {
    aUser(['email' => 'taken@example.com']);

    visit(route('register'))
        ->click('Create the account')
        ->assertSee('Fill in the first name.')
        ->fill('firstname', 'Laura')
        ->fill('email', 'taken@example.com')
        ->fill('password', 'a-Strong-pass-123')
        ->fill('password_confirmation', 'something-else')
        ->click('Create the account')
        ->assertSee('That email is already in use.')
        ->assertSee('The two password boxes do not match. Try again.')
        ->assertNoSmoke();

    expect(User::count())->toBe(1);
});

it('asks to confirm the email before anything else', function () {
    $this->as(User::factory()->unverified()->create(['email' => 'new@example.com']));

    visit(route('projects.index'))
        ->assertSee('Confirm your email')
        ->assertSee('new@example.com');
});

it('sends the confirmation link again on request', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create(['email' => 'new@example.com']);
    $this->as($user);

    visit(route('verification.notice'))
        ->click('Send the link again')
        ->assertSee('We sent you a new confirmation link.');

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('keeps someone signed in when they ask to', function () {
    $user = aUser(['email' => 'ana@example.com', 'password' => 'the-Right-pass-1']);

    visit(route('login'))
        ->fill('email', 'ana@example.com')
        ->fill('password', 'the-Right-pass-1')
        ->check('remember')
        ->click('[type="submit"]')
        ->assertPathIsNot('/login');

    expect($user->fresh()->remember_token)->not->toBeNull();
});

it('goes back to signing in from the forgotten password page', function () {
    visit(route('password.request'))
        ->click('Back to sign in')
        ->assertPathIs('/login');
});

it('signs in, refusing a wrong password', function () {
    aUser(['email' => 'ana@example.com', 'password' => 'the-Right-pass-1']);

    visit(route('login'))
        ->fill('email', 'ana@example.com')
        ->fill('password', 'a-wrong-one')
        ->click('[type="submit"]')
        ->assertSee('That email or password is not right. Check them and try again.')
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
        ->click('Send the link')
        ->assertSee('We emailed you a link to choose a new password.');

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

    visit(route('profile.edit', ['section' => 'password']))
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
    $user = aUser(['email' => 'old@example.com', 'password' => 'the-Right-pass-1']);
    $this->as($user);

    visit(route('profile.edit'))
        ->assertMissing('[name="current_password"]')
        ->fill('email', 'new@example.com')
        ->assertSee('Needed to change your email')
        ->fill('current_password', 'not-it')
        ->click('Save')
        ->assertSee('The password is incorrect.')
        ->fill('current_password', 'the-Right-pass-1')
        ->click('Save')
        ->assertSee('We sent a confirmation link to new@example.com')
        ->assertDontSee('Needed to change your email');

    expect($user->fresh()->email)->toBe('old@example.com');

    visit(emailConfirmationLinkFor($user, 'new@example.com'))
        ->assertSee('Your email address was changed.');

    expect($user->fresh()->email)->toBe('new@example.com');
});

it('deletes the account only with the password, offering a link to whoever has none', function () {
    Notification::fake();
    $user = aUser(['password' => 'the-Right-pass-1']);
    $this->as($user);

    $page = visit(route('profile.edit'))
        ->click('Delete my account')
        ->fill('[role="dialog"] >> nth=-1 >> input[type="password"]', 'not-it')
        ->click(inDialog('Delete my account'))
        ->assertSee('The password is incorrect.')
        ->click('Email me a link to choose a password')
        ->assertSee('We emailed you a link to choose a new password.');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
    expect($user->fresh())->not->toBeNull();

    $page->click('Delete my account')
        ->fill('[role="dialog"] >> nth=-1 >> input[type="password"]', 'the-Right-pass-1')
        ->click(inDialog('Delete my account'))
        ->assertSee('Your account was deleted.');

    expect(User::find($user->id))->toBeNull();
});

it('offers no removing the owner on the superadmins page', function () {
    $owner = User::factory()->superadmin()->create(['firstname' => 'Olga']);
    $owner->forceFill(['is_owner' => true])->save();
    User::factory()->superadmin()->create(['firstname' => 'Tina']);
    $this->as(User::factory()->superadmin()->create());

    visit(route('admin.superadmins.index'))
        ->assertSee('Olga')
        ->assertSee('Tina')
        ->assertCount('[data-tour="superadmins.list"] button', 1);
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

it('moves between the profile\'s sections', function () {
    $this->as(aUser());

    visit(route('profile.edit'))
        ->click('[role="tab"]:has-text("Password")')
        ->assertVisible('[name="current_password"]')
        ->assertQueryStringHas('section', 'password')
        ->click('[role="tab"]:has-text("About you")')
        ->assertVisible('[name="firstname"]')
        ->assertQueryStringMissing('section');
});

it('shares the email with the people of the projects only when asked', function () {
    $user = aUser();
    $this->as($user);

    visit(route('profile.edit'))
        ->assertNotChecked('email_public')
        ->check('email_public')
        ->click('Save')
        ->assertSee('Your profile was saved.');

    expect($user->fresh())->email_public->toBeTrue()->phone_public->toBeFalse();
});

it('changes the profile picture', function () {
    $user = aUser(['firstname' => 'Ana']);
    $this->as($user);

    // The upload done first: the picture follows it
    attachFile(visit(route('profile.edit')), 'input[type="file"]', pngFile('me.png'))
        ->assertSee('Your picture was changed.')
        ->assertPresent('.shell-account img')
        // Clicked, it opens large
        ->click('[aria-label^="See the photo of"]')
        ->assertPresent('[role="dialog"] .avatar-large');

    expect($user->fresh()->avatar)->not->toBeNull();
});

it('switches to dark mode and high contrast', function () {
    $this->as(aUser());

    // Kept for the next visit, and drawn before the page's scripts run
    $page = visit(route('profile.edit'))->click('Preferences')->click('Dark')->click('High contrast')->refresh();

    $page->assertScript("document.documentElement.getAttribute('data-mantine-color-scheme')", 'dark')
        ->assertScript("document.documentElement.hasAttribute('data-high-contrast')", true);
});

it('speaks the language one picks, on every page and from then on', function () {
    $user = aUser();
    $this->as($user);

    visit(route('profile.edit', ['section' => 'preferences']))
        ->click('Español')
        ->assertSee('Idioma')
        ->assertSee('Preferencias')
        ->assertNoSmoke();

    expect($user->fresh()->locale)->toBe('es_UY');
    visit(route('notifications.index'))->assertSee('Notificaciones');
});

it('signs out', function () {
    $this->as(aUser(['firstname' => 'Ana']));

    visit(route('projects.index'))
        ->click('[aria-label="Your account"]')
        ->click('Sign out')
        ->assertSee('Sign in');

    $this->assertGuest();
});

it('speaks every language it offers, guides included', function () {
    $project = aProject();
    $member = aMemberOf(aFamily($project, aUnitType($project)));

    foreach (['fr' => ['Préférences', 'Guide des coopérateurs'], 'pt_BR' => ['Preferências', 'Guia do cooperado'], 'it' => ['Preferenze', 'Guida per i soci']] as $locale => [$preferences, $guide]) {
        $member->update(['locale' => $locale]);
        $this->as($member->fresh());

        visit(route('profile.edit'))->assertSee($preferences)->assertNoSmoke();
        visit(route('lottery.show', $project))->assertDontSee('Your preferences')->assertNoSmoke();
        visit(route('documentation.guide'))->assertSee($guide);
    }
});

it('leaves the next person nothing of the last one in the tab', function () {
    $project = aProject();
    $ana = aMemberOf(aFamily($project), ['firstname' => 'Ana', 'lastname' => 'Pérez']);
    $beto = aMemberOf(aFamily($project), ['firstname' => 'Beto', 'lastname' => 'Silva']);
    $carla = aMemberOf(aFamily($project), ['firstname' => 'Carla', 'lastname' => 'Díaz']);
    $chat = app(ChatService::class);
    $chat->send($chat->start($project, $ana, [$carla->id]), $ana, 'Solo para Carla');
    $this->as($ana);

    $page = visit(route('projects.show', $project))
        ->click('.chat-launcher')
        ->assertSee('Solo para Carla');
    $page->script('() => { window.lastPerson = "Ana"; }');

    $page->click('[aria-label="Your account"]')->click('Sign out')->assertSee('Sign in')->wait(0.5);

    // Started over: nothing in memory, no live channel left
    expect($page->script('() => window.lastPerson ?? null'))->toBeNull()
        ->and($page->script('() => Object.keys(window.Echo?.connector?.channels ?? {})'))->toBe([]);

    $page->click('Sign in')
        ->fill('email', $beto->email)
        ->fill('password', 'password')
        ->keys('[name="password"]', 'Enter')
        ->assertSee($project->name)
        ->click('.chat-launcher')
        ->assertPresent('.chat-window')
        ->wait(0.5)
        ->assertDontSee('Solo para Carla');
});

it('shows nothing of the last person on going back after signing out', function () {
    $project = aProject();
    $ana = aMemberOf(aFamily($project), ['firstname' => 'Ana', 'lastname' => 'Pérez']);
    $this->as($ana);

    $page = visit(route('projects.show', $project))
        ->click('[aria-label="Your account"]')
        ->click('[role="menu"] >> text=Profile')
        ->assertPathIs(route('profile.edit', absolute: false))
        ->click('[aria-label="Your account"]')
        ->click('Sign out')
        ->assertSee('Sign in')
        ->wait(0.5);

    $page->script('() => history.back()');
    $page->wait(1.5);

    expect($page->content())->not->toContain($ana->email);
});
