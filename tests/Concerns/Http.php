<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Testing\TestResponse;

trait Http
{
    /**
     * Visit a Laravel route (by name) as a specific User or as Guest (default).
     */
    protected function visitRoute(string $route, int|User|null $asUser = null, bool $redirects = true): TestResponse
    {
        $this->followRedirects = $redirects;

        if ($asUser) {
            $this->actingAs(model($asUser, User::class));
        }

        return $this->getRoute($route);
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
    protected function postToRoute(string $name, array $data = [], array $parameters = []): TestResponse
    {
        return $this->post(route($name, $parameters), $data);
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

