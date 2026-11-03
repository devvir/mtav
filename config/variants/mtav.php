<?php

/**
 * MTAV: housing cooperatives and their lottery. The variant with everything.
 *
 * Each feature says what a project starts with (`default`), and whether a project may change it
 * (`configurable`). Off and not configurable, or left out, it does not exist here.
 */
$choice = ['default' => true, 'configurable' => true];

return [
    'modules'  => ['accounts', 'projects', 'platform', 'manual'],
    'features' => [
        'forum'     => $choice,
        'chat'      => $choice,
        'assistant' => $choice,
        'gallery'   => $choice,
        'files'     => $choice,
        'events'    => $choice,
    ],
    'landing' => 'Welcome',
    'privacy' => 'privacy.html',
];
