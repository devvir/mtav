<?php

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Illuminate\Database\Eloquent\Builder;

expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * Polymorphic expectations, base definitions.
 * Fail by default, perform the actual expectation with the right input later in this file.
 */

// Polymorphic HTTP expectations
expect()->extend('toBeOk', fn () => $this->fail('toBeOk is not defined for the given input'));
expect()->extend('toRedirectTo', fn () => $this->fail('toRedirectTo is not defined for the given input'));
expect()->extend('toBeUnauthorized', fn () => $this->fail('toBeUnauthorized is not defined for the given input'));
expect()->extend('toBeNotFound', fn () => $this->fail('toBeNotFound is not defined for the given input'));

// Polymorphic Eloquent expectations
expect()->extend('toExist', fn () => $this->fail('toExist is not defined for the given input'));
expect()->extend('toNotExist', fn () => $this->fail('toNotExist is not defined for the given input'));
expect()->extend('toBeAdmin', fn () => $this->fail('toBeAdmin is not defined for the given input'));
expect()->extend('toBeMember', fn () => $this->fail('toBeMember is not defined for the given input'));

/**
 * Polymorphic expectations, concrete implementations.
 * Intercept base (generic) type with concrete input types.
 */

// Expect an HTTP response's status to be 200.
expect()->intercept('toBeOk', TestResponse::class,
    fn () => $this->status()->toBe(200, message: 'The Response was not successful'));

// Expect an HTTP response's status to be redirect to a given route.
expect()->intercept('toRedirectTo', TestResponse::class,
    fn (string $route, array $params = []) => $this
        ->status()->toBe(302, message: 'The Response is not a redirect (302)')
        ->getTargetUrl()->toBe(route($route, $params), message: 'Invalid target Route'));

// Expect an HTTP response's status to be 403.
expect()->intercept('toBeUnauthorized', TestResponse::class,
    fn () => $this->status()->toBe(403, message: 'The Response was not Unauthorized (403)'));

// Expect an HTTP response's status to be 404.
expect()->intercept('toBeNotFound', TestResponse::class,
    fn () => $this->status()->toBe(404, message: 'The Response was not Not Found (404)'));

// Builder query exists()
expect()->intercept('toExist', Builder::class,
    fn () => $this->exists()->toBeTrue(message: 'The entity or entities do not exist'));
expect()->intercept('toNotExist', Builder::class,
    fn () => $this->exists()->toBeFalse(message: 'The entity or entities do exist'));

// Assert User is Admin
expect()->intercept('toBeAdmin', User::class,
    fn () => $this->is_admin->toBeTrue(message: 'The given User is not an Admin'));
expect()->intercept('toBeAdmin', User::class,
    fn () => $this->fail('The given User is null'));

// Assert User is Member
expect()->intercept('toBeMember', User::class,
    fn () => $this->is_admin->toBeFalse(message: 'The given User is not an Member'));
expect()->intercept('toBeMember', User::class,
    fn () => $this->fail('The given User is null'));