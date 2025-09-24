<?php

use App\Models\Family;
use Illuminate\Support\Facades\Config;

test('guests are redirected to the login page', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    Config::set('auth.superadmins', []);

    $user = Family::factory()
        ->withMembers()->create()
        ->members()->first();

    $this->actingAs($user);

    $response = $this->get('/');
    $response->assertStatus(200);
});
