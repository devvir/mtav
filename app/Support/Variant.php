<?php

namespace App\Support;

use Illuminate\Routing\Route;
use RuntimeException;

/**
 * What this deployment is made to be (config/variants): which parts of the app exist here, and
 * what a project may offer. Cached with the configuration, so asking costs nothing.
 */
class Variant
{
    public static function name(): string
    {
        return config('variant.name');
    }

    public static function has(string $module): bool
    {
        return in_array($module, self::spec('modules'), true);
    }

    /** Whether projects here may have this feature at all */
    public static function offers(string $feature): bool
    {
        return in_array($feature, self::spec('features'), true);
    }

    /** The page people arrive at */
    public static function landing(): string
    {
        return self::spec('landing');
    }

    /** Whether a route is open here: its middleware names the module, unless it opts out */
    public static function opens(Route $route): bool
    {
        foreach (array_diff(array_filter($route->gatherMiddleware(), 'is_string'), $route->excludedMiddleware()) as $name) {
            if (str_starts_with($name, 'module:') && ! self::has(substr($name, 7))) {
                return false;
            }
        }

        return true;
    }

    private static function spec(string $key): mixed
    {
        return config('variants.' . self::name() . ".{$key}")
            ?? throw new RuntimeException('MTAV_VARIANT names no variant in config/variants: "' . self::name() . '"');
    }
}
