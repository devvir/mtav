<?php

namespace App\Http\Props;

use App\Support\Variant;
use Illuminate\Support\Facades\Route;
use Tighten\Ziggy\Ziggy;

/**
 * The routes the browser may call: those of parts of the app this variant has, the platform
 * console's only for those who enter it
 */
class RouteListProps
{
    /** @return array<string, mixed> */
    public function of(bool $console): array
    {
        $routes = (new Ziggy())->toArray();
        $routes['routes'] = array_filter(
            $routes['routes'],
            fn (string $name) => ($console || ! str_starts_with($name, 'admin.'))
                && ($route = Route::getRoutes()->getByName($name)) && Variant::opens($route),
            ARRAY_FILTER_USE_KEY,
        );

        return $routes;
    }
}
