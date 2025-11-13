<?php

use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

/**
 * Assert that the response is an Inertia response with a specific component.
 */
function assertInertiaComponent(TestResponse $response, string $component): void
{
    $response->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
}

/**
 * Assert that Inertia response has paginated data with expected count.
 */
function assertInertiaPaginatedData(TestResponse $response, string $component, string $prop, int $expectedCount): void
{
    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
            ->component($component)
            ->has("{$prop}.data", $expectedCount)
        );
}

/**
 * Assert that Inertia response has specific data.
 */
function assertInertiaHas(TestResponse $response, string $component, string|array $props): void
{
    $response->assertOk()
        ->assertInertia(function (AssertableInertia $page) use ($component, $props) {
            $page->component($component);

            $props = is_array($props) ? $props : [$props];
            foreach ($props as $prop) {
                $page->has($prop);
            }
        });
}

/**
 * Assert that Inertia response has a prop with a specific value.
 */
function assertInertiaWhere(TestResponse $response, string $component, string $prop, $value): void
{
    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
            ->component($component)
            ->where($prop, $value)
        );
}

/**
 * Assert that Inertia response contains an error for a specific field.
 */
function assertInertiaHasError(TestResponse $response, string $field): void
{
    $response->assertInvalid($field);
}

/**
 * Get Inertia prop value from response.
 */
function getInertiaProp(TestResponse $response, string $prop)
{
    $props = null;
    $response->assertInertia(function (AssertableInertia $page) use ($prop, &$props) {
        $props = $page->toArray()['props'][$prop] ?? null;
    });

    return $props;
}

/**
 * Make an authenticated Inertia GET request.
 */
function inertiaGet($user, string $uri): TestResponse
{
    return test()->actingAs($user)->get($uri);
}

/**
 * Make an authenticated Inertia POST request.
 */
function inertiaPost($user, string $uri, array $data = []): TestResponse
{
    return test()->actingAs($user)->post($uri, $data);
}

/**
 * Make an authenticated Inertia PATCH request.
 */
function inertiaPatch($user, string $uri, array $data = []): TestResponse
{
    return test()->actingAs($user)->patch($uri, $data);
}

/**
 * Make an authenticated Inertia DELETE request.
 */
function inertiaDelete($user, string $uri): TestResponse
{
    return test()->actingAs($user)->delete($uri);
}
