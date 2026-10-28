<?php

use App\Models\User;

/**
 * The platform console, for superadmins: every account, and who else manages the platform.
 */

beforeEach(function () {
    $this->superadmin = aSuperadmin();
    $this->as($this->superadmin);
});

it('makes someone a superadmin, and takes the role back', function () {
    $ana = aUser(['firstname' => 'Ana', 'lastname' => 'García']);

    visit(route('admin.users.index'))
        ->click('.row:has-text("Ana García") >> text=Make superadmin')
        ->click(inDialog('Make superadmin'))
        ->assertSeeIn('.row:has-text("Ana García")', 'Remove superadmin');

    expect($ana->fresh()->isSuperadmin())->toBeTrue();

    visit(route('admin.users.index'))
        ->click('.row:has-text("Ana García") >> text=Remove superadmin')
        ->click(inDialog('Remove the role'))
        ->assertSeeIn('.row:has-text("Ana García")', 'Make superadmin');

    expect($ana->fresh()->isSuperadmin())->toBeFalse();
});

it('finds accounts by name or email', function () {
    aUser(['firstname' => 'Ana', 'lastname' => 'García', 'email' => 'ana@example.com']);
    aUser(['firstname' => 'Bruno', 'lastname' => 'Pérez', 'email' => 'bruno@example.com']);

    visit(route('admin.users.index'))
        ->type('[aria-label="Search by name or email"]', 'bruno@')
        ->assertSee('Bruno Pérez')
        ->assertDontSee('Ana García')
        ->assertQueryStringHas('q', 'bruno@');
});

it('pages through long lists', function () {
    User::factory()->count(45)->create();

    visit(route('admin.users.index'))
        ->click('[aria-label="Next page"]')
        ->assertQueryStringHas('page', '2')
        ->click('[aria-label="Previous page"]')
        ->assertQueryStringHas('page', '1');
});

it('previews the components in either scheme', function () {
    visit(route('admin.lab'))
        ->click('.lab-head >> text=Dark')
        ->assertScript("document.documentElement.getAttribute('data-mantine-color-scheme')", 'dark')
        ->click('.lab-head >> text=Light')
        ->assertScript("document.documentElement.getAttribute('data-mantine-color-scheme')", 'light');
});
