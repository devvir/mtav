<?php


return [
    /*
    |--------------------------------------------------------------------------
    | Namespaces
    |--------------------------------------------------------------------------
    |
    | Define the default namespaces for models, controllers, and form requests.
    | These are used by the FormService to automatically locate the appropriate
    | classes when generating form specifications.
    |
    */

    'namespaces' => [
        'models'       => 'App\\Models',
        'controllers'  => 'App\\Http\\Controllers\\Resources',
        'formrequests' => 'App\\Http\\Requests',
    ],
];
