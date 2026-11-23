<?php

use App\Support\VariantFile;

/**
 * Run the test as a deployment of the given variant: one of deploy/variants (by file name,
 * without the extension), or MTAV itself when none is named.
 */
function variant(?string $name = null): void
{
    config(['variant' => VariantFile::load($name ? base_path("deploy/variants/{$name}.yaml") : null)]);
}

/** Every variant file the repository ships: the documented example, and the ready-made copies */
function shippedVariants(): array
{
    return array_merge([base_path('variant.example.yaml')], glob(base_path('deploy/variants/*.yaml')));
}
