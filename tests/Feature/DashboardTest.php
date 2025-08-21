<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Config;

test('guests are redirected to the login page', function () {
    $response = $this->get('/');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    Config::set('auth.superadmins', []);

    Project::factory()->create()->addUser(
        $user = User::factory()->create()
    );

    $this->actingAs($user);

    $response = $this->get('/');
    $response->assertStatus(200);
});
