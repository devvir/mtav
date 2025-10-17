<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Normalize a model, model id or a collection of either, to model(s).
 *
 * @param Model|int|iterable $modelOrId A model instance, its id, or an iterable of either.
 * @param class-string<Model> $modelClass The model class to use for id(s) in $modelOrId.
 *
 * @return Model|Collection A model instance (or a collection of them if $modelOrId is iterable).
 *
 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if any id is invalid.
 */
function model(Model|int|iterable $modelOrId, string $modelClass): Model|Collection
{
    if ($modelOrId instanceof iterable) {
        return collect($modelOrId)->map(fn ($id) => model($id, $modelClass));
    }
    return $modelOrId instanceof Model
        ? $modelOrId
        : $modelClass::findOrFail($modelOrId);
}

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
