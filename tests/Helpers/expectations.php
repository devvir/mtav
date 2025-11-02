<?php

use Illuminate\Testing\TestResponse;

expect()->extend('toBeOne', fn () => $this->toBe(1));

// Polymorphic expectations
expect()->extend('toBeOk', fn () => $this->fail('toBeOk is not defined for the given input'));
expect()->extend('toRedirectTo', fn () => $this->fail('toRedirectTo is not defined for the given input'));
expect()->extend('toBeUnauthorized', fn () => $this->fail('toBeUnauthorized is not defined for the given input'));
expect()->extend('toBeNotFound', fn () => $this->fail('toBeNotFound is not defined for the given input'));

/**
 * Expect an HTTP response's status to be 200.
 */
expect()->intercept(
    'toBeOk',
    TestResponse::class,
    fn () => $this
        ->status()->toBe(200, message: 'The Response was not successful')
);

/**
 * Expect an HTTP response's status to be redirect to a given route.
 */
expect()->intercept(
    'toRedirectTo',
    TestResponse::class,
    fn (string $route, array $params = []) => $this
        ->status()->toBe(302, message: 'The Response is not a redirect (302)')
        ->getTargetUrl()->toBe(route($route, $params), message: 'Invalid target Route')
);

/**
 * Expect an HTTP response's status to be 404.
 */
expect()->intercept(
    'toBeUnauthorized',
    TestResponse::class,
    fn () => $this
        ->status()->toBe(403, message: 'The Response was not Unauthorized (403)')
);

/**
 * Expect an HTTP response's status to be 404.
 */
expect()->intercept(
    'toBeNotFound',
    TestResponse::class,
    fn () => $this
        ->status()->toBe(404, message: 'The Response was not Not Found (404)')
);