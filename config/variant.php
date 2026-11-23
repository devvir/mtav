<?php

use App\Support\VariantFile;

/**
 * What this deployment is made to be: its variant, read from its YAML file over MTAV's
 * values (variant.example.yaml documents it; documentation/planning/variants.md explains it).
 * Cached with the rest of the configuration, and read through App\Support\Variant.
 */
return VariantFile::load(env('MTAV_VARIANT_FILE') ?: base_path('variant.yaml'));
