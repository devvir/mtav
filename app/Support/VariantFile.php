<?php

namespace App\Support;

use App\Enums\ProjectFeature;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * A deployment's variant, read from its YAML file (variant.example.yaml documents every key)
 * over the values of MTAV, the variant with everything: what a deployment is with no file.
 * A key given replaces MTAV's whole; a key left out keeps it. A file that cannot be read, or
 * names something the app does not know, stops the app from starting, saying what and where.
 */
class VariantFile
{
    public const MODULES = ['accounts', 'projects', 'platform', 'manual', 'open_lottery'];

    /** @return array{modules: list<string>, features: array<string, array{default: bool, configurable: bool}>, landing: string, privacy: string, file: ?string} */
    public static function defaults(): array
    {
        $choice = ['default' => true, 'configurable' => true];

        return [
            'modules'  => ['accounts', 'projects', 'platform', 'manual'],
            'features' => array_fill_keys(array_column(ProjectFeature::cases(), 'value'), $choice),
            'landing'  => 'Welcome',
            'privacy'  => 'privacy.html',
            'file'     => null,
        ];
    }

    /** @return array{modules: list<string>, features: array<string, array{default: bool, configurable: bool}>, landing: string, privacy: string, file: ?string} */
    public static function load(?string $path): array
    {
        $spec = self::defaults();

        if ($path === null || ! is_file($path)) {
            return $spec;
        }

        try {
            $given = Yaml::parseFile($path) ?? [];
        } catch (ParseException $e) {
            throw new RuntimeException("The variant file {$path} is not valid YAML: {$e->getMessage()}");
        }

        is_array($given) || throw new RuntimeException("The variant file {$path} must hold a mapping of keys (modules, features, landing, privacy)");

        foreach (array_diff(array_keys($given), ['modules', 'features', 'landing', 'privacy']) as $unknown) {
            throw new RuntimeException("The variant file {$path} names a key the app does not know: \"{$unknown}\"");
        }

        if (array_key_exists('modules', $given)) {
            $spec['modules'] = self::modules($given['modules'], $path);
        }
        if (array_key_exists('features', $given)) {
            $spec['features'] = self::features($given['features'], $path);
        }
        foreach (['landing', 'privacy'] as $key) {
            if (array_key_exists($key, $given)) {
                is_string($given[$key]) && $given[$key] !== '' || throw new RuntimeException("The variant file {$path} must give {$key} as a non-empty string");
                $spec[$key] = $given[$key];
            }
        }
        $spec['file'] = $path;

        return $spec;
    }

    /** @return list<string> */
    private static function modules(mixed $modules, string $path): array
    {
        is_array($modules) && array_is_list($modules) || throw new RuntimeException("The variant file {$path} must give modules as a list");

        foreach ($modules as $module) {
            in_array($module, self::MODULES, true) || throw new RuntimeException("The variant file {$path} names a module the app does not have: " . json_encode($module) . ' (known: ' . implode(', ', self::MODULES) . ')');
        }

        return array_values(array_unique($modules));
    }

    /** @return array<string, array{default: bool, configurable: bool}> */
    private static function features(mixed $features, string $path): array
    {
        $known = array_column(ProjectFeature::cases(), 'value');
        is_array($features) && ! array_is_list($features) || $features === [] || throw new RuntimeException("The variant file {$path} must give features as a mapping, feature by feature");

        $spec = [];
        foreach ($features as $name => $choice) {
            in_array($name, $known, true) || throw new RuntimeException("The variant file {$path} names a feature the app does not have: \"{$name}\" (known: " . implode(', ', $known) . ')');
            is_array($choice) && is_bool($choice['default'] ?? null) && is_bool($choice['configurable'] ?? null) && count($choice) === 2
                || throw new RuntimeException("The variant file {$path} must give the feature \"{$name}\" as { default: true|false, configurable: true|false }");
            $spec[$name] = ['default' => $choice['default'], 'configurable' => $choice['configurable']];
        }

        return $spec;
    }
}
