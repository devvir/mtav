<?php

/**
 * The Assistant: the chat's guide to the app. It answers through a "brain": the API (a key with
 * a spending cap), or, on a developer's machine, the Claude command line on a signed-in account.
 * Off, it says it is not available, and nothing is sent anywhere.
 */
return [
    /** api, cli or off */
    'driver' => env('ASSISTANT_DRIVER', env('ANTHROPIC_API_KEY') ? 'api' : 'off'),

    /** The API's model and key */
    'model'   => env('ASSISTANT_MODEL', 'claude-haiku-5-5'),
    'api_key' => env('ANTHROPIC_API_KEY'),

    /** The command line's model, and the signed-in account's folder it runs on */
    'cli_model' => env('ASSISTANT_CLI_MODEL', 'haiku'),
    'cli_home'  => env('ASSISTANT_CLI_HOME'),

    /** A short answer is the point: cheap, and easy to read */
    'max_tokens' => 700,
    /** How many earlier messages it sees */
    'history' => 10,
    /** Seconds it may take to answer */
    'timeout' => 60,
    /** The longest message it takes */
    'message_chars' => 1000,

    /** Messages a day: for one person, and for the whole deployment */
    'per_person_per_day' => (int) env('ASSISTANT_PER_PERSON_PER_DAY', 40),
    'per_day'            => (int) env('ASSISTANT_PER_DAY', 2000),
];
