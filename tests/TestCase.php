<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    static $bootstrapped = false;

    protected function setUp(): void
    {
        parent::setUp();

        // @see [Laracasts] Learn Laravel and Vite @ Lesson 7
        // Disable Vite in tests to avoid manifest.json dependency
        $this->withoutVite();

        // Seed the test database (once for the whole suite)
        if (! static::$bootstrapped) {
            loadUniverse();

            static::$bootstrapped = true;
        }
    }

    protected function visitRoute(string $route, int|User $asUser, $redirects = true): TestResponse
    {
        /** @var User $user */
        $user = model($asUser, User::class);

        $this->followRedirects = $redirects;

        return $this->actingAs($user)->getRoute($route);
    }

    /**
     * Visit the given route and return the response.
     */
    protected function getRoute(string $name, array $parameters = []): TestResponse
    {
        return $this->get(route($name, $parameters));
    }

    /**
     * Send a POST request to the given route and return the response.
     */
    protected function postRoute(string $name, array $data = [], array $parameters = []): TestResponse
    {
        return $this->post(route($name, $parameters), $data);
    }

    /**
     * Alias for postRoute() that reads more naturally in English.
     */
    protected function postToRoute(string $name, array $data = [], array $parameters = []): TestResponse
    {
        return $this->postRoute($name, $data, $parameters);
    }

    /**
     * Send a PUT request to the given route and return the response.
     */
    protected function putRoute(string $name, array $data = [], array $parameters = []): TestResponse
    {
        return $this->put(route($name, $parameters), $data);
    }

    /**
     * PATCH to the given route and return the response.
     */
    protected function patchRoute(string $name, array $data = [], array $parameters = []): TestResponse
    {
        return $this->patch(route($name, $parameters), $data);
    }

    /**
     * Send a DELETE request to the given route and return the response.
     */
    protected function deleteRoute(string $name, array $parameters = []): TestResponse
    {
        return $this->delete(route($name, $parameters));
    }
}
