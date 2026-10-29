<?php

use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

function inertiaProps(TestResponse $response): array
{
    return $response->inertiaProps();
}

function inertiaProp(TestResponse $response, string $key)
{
    return $response->inertiaProps($key);
}

function inertiaRoute(TestResponse $response): ?string
{
    return inertiaProp($response, 'route');
}

function inertiaErrors(TestResponse $response): array
{
    return inertiaProp($response, 'errors');
}

/**
 * Inertia Expectations
 */
expect()->extend('toUsePage', fn () => $this->fail('toUsePage is not defined for the given input'));
expect()->extend('toHaveProps', fn () => $this->fail('toHaveProps is not defined for the given input'));
expect()->extend('toHaveProp', fn () => $this->fail('toHaveProp is not defined for the given input'));

expect()->intercept('toUsePage', TestResponse::class, function (string $component) {
    $this->value->assertInertia(fn (Assert $page) => $page->component($component));
});

expect()->intercept('toHaveProps', TestResponse::class, function (array $bindings) {
    $assertion = Arr::isAssoc($bindings) ? 'whereAll' : 'hasAll';

    $this->value->assertInertia(fn (Assert $page) => $page->$assertion($bindings));
});

expect()->intercept('toHaveProp', TestResponse::class, function (mixed $prop, mixed $propValue = null) {
    $assertion = func_num_args() === 1 ? 'has' : 'where';

    $this->value->assertInertia(fn (Assert $page) => $page->$assertion($prop, $propValue));
});

/** A prop the page loads once painted (deferred), as the page's own reload asks for it */
function deferredProp(TestResponse $response, string $key): mixed
{
    $page = $response->viewData('page');

    return test()->get($page['url'], [
        'X-Inertia'                   => 'true',
        'X-Inertia-Version'           => $page['version'],
        'X-Inertia-Partial-Component' => $page['component'],
        'X-Inertia-Partial-Data'      => $key,
    ])->json("props.{$key}");
}
