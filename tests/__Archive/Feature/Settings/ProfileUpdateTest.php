<?php

use App\Models\User;

test('profile page is displayed', function () {
    $user = User::factory()->create()->refresh();

    $response = $this
        ->actingAs($user)
        ->get('/settings/profile');

    $response->assertOk();
})->group('p1', 'member-mvp', 'profile');

test('profile information can be updated', function () {
    $user = User::factory()->create()->refresh();

    $response = $this
        ->actingAs($user)
        ->patch('/settings/profile', [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    $user->refresh();

    expect($user->firstname)->toBe('Test');
    expect($user->lastname)->toBe('User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
})->group('p1', 'member-mvp', 'profile');

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create()->refresh();

    $response = $this
        ->actingAs($user)
        ->patch('/settings/profile', [
            'firstname' => 'User',
            'lastname' => 'User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    expect($user->refresh()->email_verified_at)->not->toBeNull();
})->group('p1', 'member-mvp', 'profile');
