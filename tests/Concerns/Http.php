<?php

namespace Tests\Concerns;

use App\Models\Admin;
use App\Models\Member;
use App\Models\User;
use Illuminate\Testing\TestResponse;

trait Http
{
    /**
     * Visit a route (by name) as a specific User or as Guest (default).
     *
     * Example: $this->visitRoute('admins.index', asAdmin: 12, redirects: false)
     */
    protected function visitRoute(
        string|array $route,
        array $data = [],
        int|User|null $asUser = null,
        int|Admin|null $asAdmin = null,
        int|Member|null $asMember = null,
        bool $redirects = true,
    ): TestResponse {
        $this->prepareRequest($asUser, $asAdmin, $asMember, $redirects);

        $query = http_build_query($data);
        $uri = $this->resolveRoute($route) . ($query ? "?$query" : '');

        return $this->get($uri);
    }

    /**
     * Send a POST request to a route (by name) as a specific User or as Guest (default).
     *
     * Example: $this->submitFormToRoute('admins.create', asAdmin: 12, data: [...])
     */
    protected function submitFormToRoute(
        string|array $route,
        array $data = [],
        int|User|null $asUser = null,
        int|Admin|null $asAdmin = null,
        int|Member|null $asMember = null,
        bool $redirects = true,
    ): TestResponse {
        $this->prepareRequest($asUser, $asAdmin, $asMember, $redirects);

        return $this->post($this->resolveRoute($route), $data);
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
    protected function sendPatchRequest(
        string|array $route,
        array $data = [],
        int|User|null $asUser = null,
        int|Admin|null $asAdmin = null,
        int|Member|null $asMember = null,
        bool $redirects = true,
    ): TestResponse {
        $this->prepareRequest($asUser, $asAdmin, $asMember, $redirects);

        return $this->patch($this->resolveRoute($route), $data);
    }

    /**
     * Send a DELETE request to the given route and return the response.
     */
    protected function deleteRoute(string $name, array $parameters = []): TestResponse
    {
        return $this->delete(route($name, $parameters));
    }
    /**
     * Set up a request to a route (by name) as a specific User or as Guest (default).
     */
    private function prepareRequest(
        int|User|null $asUser = null,
        int|Admin|null $asAdmin = null,
        int|Member|null $asMember = null,
        bool $redirects = true
    ): void {
        $this->followRedirects = $redirects;

        $user = match (true) {
            isset($asUser)   => tap(model($asUser, User::class), fn ($u) => $this->actingAs($u)),
            isset($asAdmin)  => tap(model($asAdmin, User::class), fn ($u) => $this->actingAs($u)),
            isset($asMember) => tap(model($asMember, User::class), fn ($u) => $this->actingAs($u)),
            default          => null, // Acting as Guest
        };

        if ($asAdmin && $user->isNotAdmin() || $asMember && $user->isNotMember()) {
            $this->fail('Invalid User type at requestRoute()');
        }
    }

    /**
     * Convert route name and params to the URI of the corresponding named route.
     *
     * @param string|array $route  A route name, or [<name>, <param1>, ...]
     */
    private function resolveRoute(string|array $route): string
    {
        $name = is_array($route) ? array_shift($route) : $route;
        $params = is_array($route) ? $route : [];

        return route($name, $params);
    }
}
