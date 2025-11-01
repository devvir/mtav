<?php

use App\Models\User;

test('password can be confirmed', function () {
    $user = User::find(11); // Admin #11 from universe

    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});
