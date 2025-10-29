<?php

use Illuminate\Testing\TestResponse;

function inertiaRoute(TestResponse $response): ?string
{
    return $response->inertiaProps('state.route');
}