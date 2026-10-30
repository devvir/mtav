<?php

return [
    /*
    | Whether the app keeps its own errors, for superadmins to see in the console
    | (App\Services\ErrorLog): on wherever it runs for people, off while testing
    */
    'log' => env('ERROR_LOG', env('APP_ENV') !== 'testing'),
];
