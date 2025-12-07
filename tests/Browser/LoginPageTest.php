<?php

test('Login page displays expected content', function () {
    visit('/login')
        ->screenshot(filename: 'homepage')
        ->assertSee('email')
        ->assertNoSmoke();
});
