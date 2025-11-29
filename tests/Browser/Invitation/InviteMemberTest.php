<?php

test('homepage displays expected content', function () {
    visit('/login')
        ->screenshot(filename: 'homepage')
        ->assertNoSmoke();
});
