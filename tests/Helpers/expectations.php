<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Testing\TestResponse;

expect()->extend('toBeOne', fn () => $this->toBe(1));

// Collection contains exactly the given items, in any order.
expect()->extend(
    'toCollect',
    fn (...$list) => expect($this->value->all())->toEqualCanonicalizing($list)
);

/**
 * Polymorphic expectations, base definitions.
 * Fail by default, perform the actual expectation with the right input later in this file.
 */

// Polymorphic HTTP expectations
expect()->extend('toBeOk', fn () => $this->fail('toBeOk is not defined for the given input'));
expect()->extend('toRedirectTo', fn () => $this->fail('toRedirectTo is not defined for the given input'));
expect()->extend('toBeForbidden', fn () => $this->fail('toBeForbidden is not defined for the given input'));
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
expect()->intercept(
    'toBeOk',
    TestResponse::class,
    fn () => $this->value->assertOk()
);

// Expect an HTTP response's status to be redirect to a given route.
expect()->intercept(
    'toRedirectTo',
    TestResponse::class,
    fn (string $route, array $params = []) => $this->value->assertRedirect(route($route, $params))
);

// Expect an HTTP response's status to be 403.
expect()->intercept(
    'toBeForbidden',
    TestResponse::class,
    fn () => $this->value->assertForbidden()
);

// Expect an HTTP response's status to be 404.
expect()->intercept(
    'toBeNotFound',
    TestResponse::class,
    fn () => $this->value->assertNotFound()
);

// Builder query exists()
expect()->intercept(
    'toExist',
    Builder::class,
    fn () => $this->exists()->toBeTrue(message: 'The entity or entities do not exist')
);
expect()->intercept(
    'toNotExist',
    Builder::class,
    fn () => $this->exists()->toBeFalse(message: 'The entity or entities do exist')
);

// Assert User is Admin
expect()->intercept(
    'toBeAdmin',
    User::class,
    fn () => $this->is_admin->toBeTrue(message: 'The given User is not an Admin')
);
expect()->intercept(
    'toBeAdmin',
    User::class,
    fn () => $this->fail('The given User is null')
);

// Assert User is Member
expect()->intercept(
    'toBeMember',
    User::class,
    fn () => $this->is_admin->toBeFalse(message: 'The given User is not an Member')
);
expect()->intercept(
    'toBeMember',
    User::class,
    fn () => $this->fail('The given User is null')
);
