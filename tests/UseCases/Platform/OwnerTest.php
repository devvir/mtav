<?php

use App\Http\Middleware\RequireOwner;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

/**
 * A deployment is used only once it has an owner, its first superadmin: from its settings
 * while it has no account at all; until then it is closed, under construction.
 */
beforeEach(function () {
    RequireOwner::$owned = false;
    Cache::forget('mtav.has-owner');
});

it('stays closed, under construction, while it has no account and no owner set', function () {
    config(['app.owner' => ['email' => null, 'password' => null, 'name' => null]]);

    $this->get(route('home'))->assertStatus(503)->assertInertia(fn ($page) => $page->component('UnderConstruction'));
    $this->get(route('login'))->assertStatus(503);
    expect(User::count())->toBe(0);
});

it('keeps its public documents readable while closed', function () {
    config(['app.owner' => ['email' => null, 'password' => null, 'name' => null]]);

    $this->get(route('privacy'))->assertOk()->assertInertia(fn ($page) => $page->component('PublicDocument'));
    $this->get(route('manual'))->assertOk();
    $this->get(route('manifest'))->assertOk();
    expect(User::count())->toBe(0);
});

it('creates its owner from the settings on the first visit, a superadmin who signs in', function () {
    config(['app.owner' => ['email' => 'Owner@Example.com', 'password' => 'the-Owner-pass-1', 'name' => 'Olga']]);

    $this->get(route('home'))->assertOk();
    $this->post(route('login'), ['email' => 'owner@example.com', 'password' => 'the-Owner-pass-1']);

    $owner = User::sole();
    expect($owner)->email->toBe('owner@example.com')->firstname->toBe('Olga')
        ->is_owner->toBeTrue()->is_superadmin->toBeTrue()
        ->and($owner->hasVerifiedEmail())->toBeTrue();
    $this->assertAuthenticatedAs($owner);
});

it('ignores the settings once anyone has an account', function () {
    aUser(['email' => 'first@example.com']);
    config(['app.owner' => ['email' => 'owner@example.com', 'password' => 'the-Owner-pass-1', 'name' => null]]);

    $this->get(route('home'))->assertOk();

    expect(User::pluck('email')->all())->toBe(['first@example.com']);
});

it('makes the first seeded account the owner where demo data is seeded', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('is_owner', true)->pluck('email')->all())->toBe(['superadmin@example.com']);
})->group('slow');
