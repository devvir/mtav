<?php

use App\Models\Family;
use Illuminate\Support\Facades\Config;

test('guests are redirected to the login page', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
})->group('p0', 'member-mvp', 'dashboard', 'auth');

test('authenticated users can visit the dashboard', function () {
    $member = Family::factory()
        ->withMembers()->create()
        ->members()->first();

    // Get the User instance instead of the Member instance
    $user = \App\Models\User::find($member->id);

    $this->actingAs($user);

    $response = $this->get('/');
    $response->assertStatus(200);
})->group('p0', 'member-mvp', 'dashboard');
