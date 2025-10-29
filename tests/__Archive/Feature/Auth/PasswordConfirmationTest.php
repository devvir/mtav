<?php

use App\Models\User;

test('password can be confirmed', function () {
    $user = User::factory()->create()->refresh();

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});
