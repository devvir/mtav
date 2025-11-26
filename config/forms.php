<?php

use App\Models\Unit;
use App\Models\User;

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

    /*
    |--------------------------------------------------------------------------
    | Form Field Label Mappings
    |--------------------------------------------------------------------------
    |
    | Define which field to use as the label for select options when building
    | forms for relations.
    |
    | Format:
    |   Model::class => 'field_name'  // Use a specific field
    |   Model::class => fn($model) => 'string'  // Use a Closure for custom logic
    |
    | The label field defaults to 'name' for all models. You may specify models
    | here that use a different field or a custom Closure for their label.
    |
    */

    'optionLabel' => [
        User::class => fn (User $user) => "{$user->firstname} {$user->lastname}",
        Unit::class => 'identifier',
    ],
];
