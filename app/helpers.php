<?php

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

function state(string $key, mixed $default = null): mixed
{
    return session()->get("state.$key", $default);
}

function updateState(string $key, mixed $value): void
{
    session()->put("state.$key", $value);
}
