<?php

use App\Support\VariantFile;

/**
 * What this deployment is made to be: its variant, read from a YAML file over MTAV's values
 * (variant.example.yaml documents it; documentation/planning/variants.md explains it). The file
 * is, first, the one MTAV_VARIANT_FILE names; else the environment's own, variants/<MTAV_ENV>.yaml,
 * which the release image carries; else, in development, variant.yaml at the root. None: MTAV.
 * Cached with the rest of the configuration, and read through App\Support\Variant.
 */
$environment = (string) env('MTAV_ENV');
$deployed = preg_match('/^[a-z][a-z0-9-]*$/', $environment) ? base_path("variants/{$environment}.yaml") : null;

return VariantFile::load(env('MTAV_VARIANT_FILE') ?: ($deployed && is_file($deployed) ? $deployed : base_path('variant.yaml')));
