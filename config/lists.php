<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Whole lists
    |--------------------------------------------------------------------------
    |
    | Up to this many items, a list (people, families) comes whole and the browser
    | searches and filters it, with no request per keystroke. Past it, the server pages
    | the list and applies the search and the filters.
    |
    */

    'whole' => (int) env('LISTS_WHOLE', 200),

    /*
    |--------------------------------------------------------------------------
    | Page sizes
    |--------------------------------------------------------------------------
    |
    | How many items a page of each paged list holds.
    |
    */

    'per_page' => [
        'people'        => 40,
        'families'      => 30,
        'events'        => 30,
        'media'         => 24,
        'topics'        => 30,
        'posts'         => 30,
        'notifications' => 30,
        'directory'     => 24,
        // The console's
        'projects' => 30,
        'users'    => 40,
        'reports'  => 20,
        'errors'   => 30,
        'activity' => 50,
    ],
];
