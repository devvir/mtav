<?php

use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/******************************************************************************
 *** Authorization ************************************************************
 *****************************************************************************/

/**
 * Alias for `Auth::user()->can()`.
 *
 * @see \Illuminate\Foundation\Auth\Access\Authorizable
 */
function can(iterable|UnitEnum|string  $abilities, ...$arguments): bool
{
    return Auth::user()?->can($abilities, $arguments) ?? false;
}

/**
 * Alias for `Auth::user()->canAny()`.
 *
 * @see \Illuminate\Foundation\Auth\Access\Authorizable
 */
function canAny(iterable|UnitEnum|string  $abilities, ...$arguments): bool
{
    return Auth::user()?->canAny($abilities, $arguments) ?? false;
}

/**
 * Alias for `Auth::user()->cannot()`.
 *
 * @see \Illuminate\Foundation\Auth\Access\Authorizable
 */
function cannot(iterable|UnitEnum|string $abilities, ...$arguments): bool
{
    return Auth::user()?->cannot($abilities, $arguments) ?? true;
}

/**
 * Alias for `Gate::authorize()`.
 *
 * @see \Illuminate\Auth\Access\Gate
 */
function authorize(iterable|UnitEnum|string $ability, ...$arguments): Response
{
    return Gate::authorize($ability, $arguments);
}

/******************************************************************************
 *** Enums ********************************************************************
 *****************************************************************************/

function enumFromValue(string $enumClass, string $value): UnitEnum
{
    if (! is_subclass_of($enumClass, UnitEnum::class)) {
        throw new InvalidArgumentException("'$enumClass' is not a valid Enum FQN.");
    }

    foreach ($enumClass::cases() as $case) {
        if ($value === $case->value ?? $case->name) {
            return $case;
        }
    }

    throw new ValueError("Invalid name/value '$value' for enum '$enumClass'");
}


/******************************************************************************
 *** State ********************************************************************
 *****************************************************************************/

function state(string $key, mixed $default = null): mixed
{
    return session()->get("state.$key", $default);
}

function setState(string $key, mixed $value): void
{
    session()->put("state.$key", $value);
}
