<?php

namespace Tests\Concerns;

use App\Models\Admin;
use App\Models\Member;
use App\Models\User;
use Illuminate\Testing\TestResponse;

trait Http
{
    /**
     * Visit a Laravel route (by name) as a specific User or as Guest (default).
     */
    protected function visitRoute(
        string|array $route,
        int|User|null $asUser = null,
        int|Admin|null $asAdmin = null,
        int|Member|null $asMember = null,
        bool $redirects = true
    ): TestResponse {
        $this->followRedirects = $redirects;

        $user = match (true) {
            isset($asUser) => tap(model($asUser, User::class), fn ($u) => $this->actingAs($u)),
            isset($asAdmin) => tap(model($asAdmin, User::class), fn ($u) => $this->actingAs($u)),
            isset($asMember) => tap(model($asMember, User::class), fn ($u) => $this->actingAs($u)),
            default => null, // Acting as Guest
        };

        if ($asAdmin && $user->isNotAdmin() || $asMember && $user->isNotMember()) {
             $this->fail('Invalid User type passed to visitRoute()');
        }

        return $this->getRoute($route);
    }

    /**
     * Visit the given route and return the response.
     *
     * @param string|array $route  A route name, or [<name>, <param1>, ...]
     */
    protected function getRoute(string|array $route): TestResponse
    {
        $name = is_array($route) ? array_shift($route) : $route;
        $params = is_array($route) ? $route : [];

        return $this->get(route($name, $params));
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

