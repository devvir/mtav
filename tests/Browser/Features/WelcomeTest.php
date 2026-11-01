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

it('shows every photo it names, families building among them', function () {
    $page = visit(route('home'))->assertSee('Families building their homes, by mutual aid');

    // Brought into view, so that the lazy ones load too
    $page->script("() => document.querySelectorAll('.welcome-photo img').forEach((img) => { img.loading = 'eager'; })");
    $page->wait(1);

    expect($page->script("() => [...document.querySelectorAll('.welcome-photo img')].filter((img) => !img.naturalWidth).map((img) => img.src)"))->toBe([]);
});

it('never reads out a picture changing by itself, only one someone picked', function () {
    visit(route('home'))
        ->assertAttribute('.welcome-hero-slides figcaption', 'aria-live', 'off')
        ->click('.welcome-hero-dots button >> nth=1')
        ->assertAttribute('.welcome-hero-slides figcaption', 'aria-live', 'polite');
});

it('stops its first pictures for good with a button, and starts them again', function () {
    $caption = fn ($page) => $page->script("() => document.querySelector('.welcome-hero-slides figcaption').textContent");

    $page = visit(route('home'))
        ->click('[aria-label="Pause the pictures"]')
        ->assertPresent('[aria-label="Play the pictures"]')
        ->assertAttribute('.welcome-hero-slides figcaption', 'aria-live', 'polite')
        // Away from the pictures, where they would otherwise go on
        ->hover('.welcome-heading >> nth=0');
    $stopped = $caption($page);

    $page->wait(7);
    expect($caption($page))->toBe($stopped);

    $page->click('[aria-label="Play the pictures"]')
        ->hover('.welcome-heading >> nth=0')
        ->assertAttribute('.welcome-hero-slides figcaption', 'aria-live', 'off')
        ->wait(7);
    expect($caption($page))->not->toBe($stopped);
});

it('keeps a picture someone picked, whatever the pointer does next', function () {
    $page = visit(route('home'))
        ->click('.welcome-hero-dots button >> nth=1')
        ->hover('.welcome-heading >> nth=0');
    $picked = $page->script("() => document.querySelector('.welcome-hero-slides figcaption').textContent");

    $page->wait(7);

    expect($page->script("() => document.querySelector('.welcome-hero-slides figcaption').textContent"))->toBe($picked);
});

it('shows its first pictures one after another, VICMAN among them', function () {
    visit(route('home'))
        ->click('.welcome-hero-dots button >> nth=0')
        ->assertSeeIn('.welcome-hero-slides figcaption', 'VICMAN, the first housing cooperative in Uruguay')
        ->assertPresent('.welcome-hero-frame img[data-shown][src*="vicman"]');
});
