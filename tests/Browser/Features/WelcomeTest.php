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

it('finds the platform console after a superadmin signs in over the page', function () {
    $superadmin = aUser(['email' => 'root@example.com', 'password' => 'the-Right-pass-1', 'is_superadmin' => true]);

    visit(route('login'))
        ->fill(inDialogField('email'), 'root@example.com')
        ->fill(inDialogField('password'), 'the-Right-pass-1')
        ->click('[role="dialog"] [type="submit"]')
        ->assertPathIsNot('/login')
        ->click('[aria-label="Your account"]')
        ->click('[role="menu"] >> text=Platform console')
        ->assertPathIs('/admin')
        ->assertNoSmoke();

    $this->assertAuthenticatedAs($superadmin);
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

    $page = visit(route('home'))
        ->click('header >> ' . button('English'))
        ->click('text=Español')
        ->assertSee('De la primera asamblea a la entrega de llaves')
        ->click('header >> ' . button('Entrar'))
        ->fill(inDialogField('email'), 'ana@example.com')
        ->fill(inDialogField('password'), 'the-Right-pass-1')
        ->click('[role="dialog"] [type="submit"]');

    eventually($page, fn () => expect($user->fresh()->locale)->toBe('es_UY'));
});

it('shows every photo it names, families building among them', function () {
    $page = visit(route('home'))->assertSee('Families building their homes, by mutual aid');

    // Brought into view, so that the lazy ones load too
    $page->script("() => document.querySelectorAll('.welcome-photo img').forEach((img) => { img.loading = 'eager'; })");

    $page->assertScript("[...document.querySelectorAll('.welcome-photo img')].every((img) => img.complete)", true);
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

    // A picture's time (6 s) run ahead on the page's clock, not waited for
    $page = waitUntil(fakeClock(visit(route('home')))->refresh(), "document.querySelector('.welcome-hero-dots')")
        ->click('[aria-label="Pause the pictures"]')
        ->assertPresent('[aria-label="Play the pictures"]')
        ->assertAttribute('.welcome-hero-slides figcaption', 'aria-live', 'polite')
        // Away from the pictures, where they would otherwise go on
        ->hover('.welcome-heading >> nth=0');
    $stopped = $caption($page);

    runClock($page, 7000);
    expect($caption($page))->toBe($stopped);

    $page->click('[aria-label="Play the pictures"]')
        ->hover('.welcome-heading >> nth=0')
        ->assertAttribute('.welcome-hero-slides figcaption', 'aria-live', 'off');
    runClock($page, 7000)->assertDontSeeIn('.welcome-hero-slides figcaption', $stopped);
});

it('keeps a picture someone picked, whatever the pointer does next', function () {
    $page = waitUntil(fakeClock(visit(route('home')))->refresh(), "document.querySelector('.welcome-hero-dots')")
        ->click('.welcome-hero-dots button >> nth=1')
        ->hover('.welcome-heading >> nth=0');
    $picked = $page->script("() => document.querySelector('.welcome-hero-slides figcaption').textContent");

    runClock($page, 7000);

    expect($page->script("() => document.querySelector('.welcome-hero-slides figcaption').textContent"))->toBe($picked);
});

it('shows its first pictures one after another, VICMAN among them', function () {
    visit(route('home'))
        ->click('.welcome-hero-dots button >> nth=0')
        ->assertSeeIn('.welcome-hero-slides figcaption', 'VICMAN, the first housing cooperative in Uruguay')
        ->assertPresent('.welcome-hero-frame img[data-shown][src*="vicman"]');
});

it('shows the cooperatives in six pictures, FUCVAM\'s with their photographer\'s credit', function () {
    visit(route('home'))
        ->assertCount('.welcome-hero-dots button', 6)
        ->click('.welcome-hero-dots button >> nth=1')
        ->assertSeeIn('.welcome-hero-slides figcaption', 'Photo: Gustavo Castagnello / FUCVAM, 2016')
        ->assertPresent('.welcome-hero-frame img[data-shown][src*="jornada-solidaria"]')
        ->click('.welcome-hero-dots button >> nth=4')
        ->assertSeeIn('.welcome-hero-slides figcaption', 'Photo: Gustavo Castagnello / FUCVAM, 2007');
});

it('enters as a demo account as soon as it is picked, found by its role too, with no password', function () {
    config(['app.deployment' => 'staging']);
    aUser(['email' => 'pedro@example.com', 'firstname' => 'Pedro', 'lastname' => 'Rey']);
    $ana = anAdminOf(aProject());
    $ana->update(['email' => 'ana@example.com', 'firstname' => 'Ana', 'lastname' => 'Silva']);

    visit(route('login'))
        ->fill('[role="dialog"] >> internal:label="Enter as a demo account"', 'admin')
        ->assertSeeIn('[role="option"]:has-text("Ana Silva")', 'Admin')
        ->assertDontSee('Pedro Rey')
        ->click('[role="option"]:has-text("Ana Silva")')
        ->assertPathIsNot('/login')
        ->assertNoSmoke();

    $this->assertAuthenticatedAs($ana);
});

it('shows no demo accounts where there are none', function () {
    config(['app.deployment' => 'production']);
    aUser(['email' => 'ana@example.com']);

    visit(route('login'))
        ->assertSee('Keep me signed in')
        ->assertDontSee('Enter as a demo account')
        ->assertNoSmoke();
});
