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
];
