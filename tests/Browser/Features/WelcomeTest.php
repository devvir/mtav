<?php

/**
 * Arriving: the landing page, signing in over it, its language carried into the account.
 */

it('presents the platform, and signs in over the page', function () {
    $user = aUser(['email' => 'ana@example.com', 'password' => 'the-Right-pass-1']);

    visit(route('home'))
        ->assertSee('From the first assembly to the keys')
        ->click('header >> ' . button('Sign in'))
        ->assertPathIs('/login')
        ->fill(inDialogField('email'), 'ana@example.com')
        ->fill(inDialogField('password'), 'the-Right-pass-1')
        ->click('[role="dialog"] [type="submit"]')
        ->assertPathIsNot('/login')
        ->assertNoSmoke();

    $this->assertAuthenticatedAs($user);
});

it('moves between signing in and signing up within the dialog', function () {
    visit(route('login'))
        ->click('[role="dialog"] >> text="Create an account"')
        ->assertPathIs('/register')
        ->assertPresent(inDialogField('firstname'))
        ->click('[role="dialog"] [aria-label="Close"], [role="dialog"] .mantine-Modal-close')
        ->assertPathIs('/');
});

it('speaks the language a guest picks, and their account keeps it', function () {
    $user = aUser(['email' => 'ana@example.com', 'password' => 'the-Right-pass-1', 'locale' => 'en']);

    visit(route('home'))
        ->click('header >> ' . button('English'))
        ->click('text=Español')
        ->assertSee('De la primera asamblea a la entrega de llaves')
        ->click('header >> ' . button('Entrar'))
        ->fill(inDialogField('email'), 'ana@example.com')
        ->fill(inDialogField('password'), 'the-Right-pass-1')
        ->click('[role="dialog"] [type="submit"]')
        ->assertPathIsNot('/login');

    expect($user->fresh()->locale)->toBe('es_UY');
});
