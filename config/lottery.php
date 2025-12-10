<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Lottery Solver
    |--------------------------------------------------------------------------
    |
    | This option controls the default solver used for lottery execution.
    | The solver determines how units are assigned to families based on
    | their preferences.
    |
    | Supported: "random", "test", or any custom solver defined below
    |
    */

    'default' => env('LOTTERY_SOLVER', 'random'),

    /*
    |--------------------------------------------------------------------------
    | Lottery Solvers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the lottery solvers for your application.
    | Each solver requires a fully qualified class name and a config array.
    |
    | To add a new solver:
    |  1. Create a class implementing SolverInterface
    |  2. Add a new entry below with a unique key
    |  3. Specify the solver class and any required config
    |  4. Set LOTTERY_SOLVER env variable and any additional config variable
    |     that your Solver needs (e.g. API keys, secrets, etc.)
    |
    */

    'solvers' => [

        'random' => [
            'solver' => \App\Services\Lottery\Solvers\RandomSolver::class,
        ],

        'test' => [
            'solver' => \App\Services\Lottery\Solvers\TestSolver::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Local GLPK Solver (Production)
        |--------------------------------------------------------------------------
        |
        | Uses GLPK (GNU Linear Programming Kit) installed locally in the container
        | for optimal max-min fairness lottery assignments.
        |
        | Requirements: glpk-utils package installed (see Dockerfile)
        |
        */

        'glpk' => [
            'solver' => \App\Services\Lottery\Solvers\GlpkSolver::class,
            'config' => [
                'glpsol_path' => env('GLPK_SOLVER_PATH', '/usr/bin/glpsol'),
                'temp_dir'    => env('GLPK_TEMP_DIR', sys_get_temp_dir()),
                'timeout'     => env('GLPK_TIMEOUT', 120),
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Example: External API Solver
        |--------------------------------------------------------------------------
        |
        | Sample configuration for an solver that uses an external optimization API.
        |
        */

        // 'acme' => [
        //     'solver' => \App\Services\Lottery\Solvers\AcmeSolver::class,
        //     'config' => [
        //         'api_key' => env('ACME_API_KEY'),
        //         'api_secret' => env('ACME_API_SECRET'),
        //         'api_endpoint' => env('ACME_API_ENDPOINT', 'https://api.acme.com/lottery'),
        //         'timeout' => env('ACME_API_TIMEOUT', 30),
        //         'retry_attempts' => env('ACME_API_RETRY_ATTEMPTS', 3),
        //         'verify_ssl' => env('ACME_API_VERIFY_SSL', true),
        //     ],
        // ],

    ],

];
