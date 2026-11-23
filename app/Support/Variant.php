<?php

namespace App\Support;

use Illuminate\Routing\Route;

/**
 * What this deployment is made to be (config/variant, from its variant.yaml): which parts of
 * the app exist here, and what a project may offer. Cached with the configuration, so asking
 * costs nothing.
 */
class Variant
{
    /** The file this deployment's variant was read from; null: MTAV, with no file */
    public static function file(): ?string
    {
        return config('variant.file');
    }

    public static function has(string $module): bool
    {
        return in_array($module, config('variant.modules'), true);
    }

    /** @return array{default: bool, configurable: bool}|null  null: it does not exist here */
    public static function feature(string $feature): ?array
    {
        $spec = config('variant.features')[$feature] ?? null;

        return $spec && ($spec['default'] || $spec['configurable']) ? $spec : null;
    }

    /** @return array<string, bool>  what a project here may choose, and what it starts with */
    public static function choices(): array
    {
        return collect(config('variant.features'))->filter(fn (array $spec) => $spec['configurable'])
            ->map(fn (array $spec) => $spec['default'])
            ->all();
    }

    /** The page people arrive at */
    public static function landing(): string
    {
        return config('variant.landing');
    }

    /** Its privacy page, one of resources/views/legal/<locale>/ */
    public static function privacy(): string
    {
        return config('variant.privacy');
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
}
