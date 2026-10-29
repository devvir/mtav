<?php

/**
 * Deploying from the platform console: who may, and the GitHub repository whose workflows
 * deploy (see documentation/technical/delivery.md).
 */
return [
    // Superadmins who may deploy, by email; empty: nobody (the page is hidden)
    'deployers' => array_values(array_filter(array_map(
        fn ($email) => strtolower(trim($email)),
        explode(',', (string) env('DEPLOYERS', '')),
    ))),

    'github' => [
        'repository' => env('GITHUB_REPOSITORY', 'devvir/mtav'),
        // Fine-grained, this repository only: Actions read and write, Deployments and
        // Contents read
        'token' => env('GITHUB_DEPLOY_TOKEN'),
    ],

    // Each environment and where it is reached, in the order shown
    'environments' => [
        'production' => 'https://www.mtav.uy',
        'staging'    => 'https://stg.mtav.uy',
        'dev'        => 'https://dev.mtav.uy',
    ],
];
