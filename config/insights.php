<?php

declare(strict_types=1);

use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenDefineFunctions;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenNormalClasses;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenPrivateMethods;
use NunoMaduro\PhpInsights\Domain\Insights\ForbiddenTraits;
use NunoMaduro\PhpInsights\Domain\Metrics\Architecture\Classes;
use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UselessOverridingMethodSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Commenting\TodoSniff;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Files\LineLengthSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\AssignmentInConditionSniff;
use SlevomatCodingStandard\Sniffs\ControlStructures\DisallowShortTernaryOperatorSniff;
use SlevomatCodingStandard\Sniffs\Functions\UnusedParameterSniff;
use SlevomatCodingStandard\Sniffs\PHP\UselessParenthesesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DeclareStrictTypesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowMixedTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\PropertyTypeHintSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\ReturnTypeHintSniff;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Preset
    |--------------------------------------------------------------------------
    |
    | This option controls the default preset that will be used by PHP Insights
    | to make your code reliable, simple, and clean. However, you can always
    | adjust the `Metrics` and `Insights` below in this configuration file.
    |
    | Supported: "default", "laravel", "symfony", "magento2", "drupal", "wordpress"
    |
    */

    'preset' => 'laravel',

    /*
    |--------------------------------------------------------------------------
    | IDE
    |--------------------------------------------------------------------------
    |
    | This options allow to add hyperlinks in your terminal to quickly open
    | files in your favorite IDE while browsing your PhpInsights report.
    |
    | Supported: "textmate", "macvim", "emacs", "sublime", "phpstorm",
    | "atom", "vscode".
    |
    | If you have another IDE that is not in this list but which provide an
    | url-handler, you could fill this config with a pattern like this:
    |
    | myide://open?url=file://%f&line=%l
    |
    */

    'ide' => 'vscode',

    /*
    |--------------------------------------------------------------------------
    | Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may adjust all the various `Insights` that will be used by PHP
    | Insights. You can either add, remove or configure `Insights`. Keep in
    | mind, that all added `Insights` must belong to a specific `Metric`.
    |
    */

    'exclude' => [
        'packages',
        'app/Relations',
    ],

    'add' => [
        Classes::class => [
        ],
    ],

    'remove' => [
        // Disallows assignment within if/while conditions.
        // Example violation: `if ($user = Auth::user()) { ... }`
        AssignmentInConditionSniff::class,

        // Requires `declare(strict_types=1);` at the top of every file.
        // Example violation: Missing `declare(strict_types=1);`
        DeclareStrictTypesSniff::class,

        // Disallows `mixed` type hints.
        // Example violation: `public function process(mixed $data): mixed`
        DisallowMixedTypeHintSniff::class,

        // Disallows short ternary operator (Elvis operator).
        // Example violation: `$value = $foo ?: 'default';`
        DisallowShortTernaryOperatorSniff::class,

        // Requires all classes to be either `final` or `abstract`.
        // Example violation: `class User extends Model { }` (should be `final class User extends Model { }`)
        ForbiddenNormalClasses::class,

        // Disallows use of traits.
        // Example violation: `use HasFactory, SoftDeletes;`
        ForbiddenTraits::class,

        // Requires @return annotations for methods returning traversable values.
        // Example violation: `public function getItems(): array` without `@return array<int, Item>`
        ReturnTypeHintSniff::class,

        // Flags TODO comments in code.
        // Example violation: `TODO: refactor this later`
        TodoSniff::class,

        // Flags useless parentheses in expressions.
        // Example violation: `if (($x === 1)) { }`
        UselessParenthesesSniff::class,
    ],

    'config' => [
        BinaryOperatorSpacesFixer::class => [
            'operators' => [
                '=>' => 'align_single_space',
            ],
        ],
        ForbiddenDefineFunctions::class => [
            'exclude' => ['app/Enums', 'app/helpers.php'],
        ],
        ForbiddenPrivateMethods::class => [
            'title' => 'The usage of private methods is not idiomatic in Laravel.',
        ],
        LineLengthSniff::class => [
            'lineLimit'         => 120,
            'absoluteLineLimit' => 120,
            'exclude'           => ['lang'],
        ],
        PropertyTypeHintSniff::class => [
            // Exclude Models: Laravel's Eloquent Model properties ($casts, $guarded, $fillable, etc.)
            // are inherited from parent class and cannot have their types redeclared in child classes
            'exclude' => ['app/Models', 'app/Middleware'],
        ],
        UnusedParameterSniff::class => [
            'allowedParameterPatterns' => ['~^_$~'],
            // Exclude Policies: $user parameters serve as authentication gatekeepers via type hinting
            // Even when not directly used, "User $user" ensures only authenticated users can reach the method
            'exclude' => ['app/Policies'],
        ],
        UselessOverridingMethodSniff::class => [
            'exclude' => ['app/Http/Requests']
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Requirements
    |--------------------------------------------------------------------------
    |
    | Here you may define a level you want to reach per `Insights` category.
    | When a score is lower than the minimum level defined, then an error
    | code will be returned. This is optional and individually defined.
    |
    */

    'requirements' => [
        //        'min-quality' => 0,
        //        'min-complexity' => 0,
        //        'min-architecture' => 0,
        //        'min-style' => 0,
        //        'disable-security-check' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Threads
    |--------------------------------------------------------------------------
    |
    | Here you may adjust how many threads (core) PHPInsights can use to perform
    | the analysis. This is optional, don't provide it and the tool will guess
    | the max core number available. It accepts null value or integer > 0.
    |
    */

    'threads' => null,

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    | Here you may adjust the timeout (in seconds) for PHPInsights to run before
    | a ProcessTimedOutException is thrown.
    | This accepts an int > 0. Default is 60 seconds, which is the default value
    | of Symfony's setTimeout function.
    |
    */

    'timeout' => 60,
];
