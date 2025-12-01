<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Lottery Executor
    |--------------------------------------------------------------------------
    |
    | This option controls the default executor used for lottery execution.
    | The executor determines how units are assigned to families based on
    | their preferences.
    |
    | Supported: "random", "test", or any custom executor defined below
    |
    */

    'default' => env('LOTTERY_EXECUTOR', 'random'),

    /*
    |--------------------------------------------------------------------------
    | Lottery Executors
    |--------------------------------------------------------------------------
    |
    | Here you may configure the lottery executors for your application.
    | Each executor requires a fully qualified class name and a config array.
    |
    | To add a new executor:
    |  1. Create a class implementing ExecutorInterface
    |  2. Add a new entry below with a unique key
    |  3. Specify the executor class and any required config
    |  4. Set LOTTERY_EXECUTOR env variable and any additional config variable
    |     that your Executor needs (e.g. API keys, secrets, etc.)
    |
    */

    'executors' => [

        'random' => [
            'executor' => \App\Services\Lottery\Executors\RandomExecutor::class,
        ],

        'test' => [
            'executor' => \App\Services\Lottery\Executors\TestExecutor::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Example: External API Executor
        |--------------------------------------------------------------------------
        |
        | Sample configuration for an executor that uses an external optimization API.
        |
        */

        // 'acme' => [
        //     'executor' => \App\Services\Lottery\Executors\AcmeExecutor::class,
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
