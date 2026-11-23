<?php

use App\Support\VariantFile;

/**
 * Run the test as a deployment of the given variant: one of variants/ (by its environment's
 * name), or MTAV itself when none is named.
 */
function variant(?string $name = null): void
{
    config(['variant' => VariantFile::load($name ? base_path("variants/{$name}.yaml") : null)]);
}

/** Every variant file the repository ships: the documented example, and each deployment's own */
function shippedVariants(): array
{
    return array_merge([base_path('variant.example.yaml')], glob(base_path('variants/*.yaml')));
}
