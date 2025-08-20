<?php

use App\Http\Middleware\Auth\MustBeAdmin;
use App\Http\Middleware\Auth\MustBeSuperAdmin;
use App\Models\Family;
use App\Models\Log;
use App\Models\Project;
use App\Models\Unit;
use App\Models\User;

return [
    'resources' => [
        User::class => [
            'middleware' => [
                MustBeAdmin::class => ['store', 'update', 'destroy'],
            ],
        ],
        Family::class => [
            'middleware' => [
                MustBeAdmin::class => ['store', 'update', 'destroy'],
            ],
        ],
        Project::class => [
            'middleware' => [
                MustBeAdmin::class => ['index', 'update'],
                MustBeSuperAdmin::class => ['store', 'destroy'],
            ],
        ],
        Unit::class => [
            'middleware' => [
                MustBeAdmin::class => ['store', 'update', 'destroy'],
            ],
        ],
        Log::class => [
            'only' => ['index', 'show'],
        ],
    ],
];
