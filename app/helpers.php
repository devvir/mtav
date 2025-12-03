<?php

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * For a model or model id, return the model.
 *
 * @param class-string<Model> $modelClass
 *
 * @throws ModelNotFoundException if $modelOrId is an invalid id for $modelClass
 */
function model(Model|int $modelOrId, string $modelClass, bool $withTrashed = false): Model
{
    return $modelOrId instanceof Model
        ? $modelOrId
        : $modelClass::when($withTrashed, fn ($q) => $q->withTrashed())->findOrFail($modelOrId);
}

/**
 * @param Model|int|iterable<int> $modelsOrIds
 * @param class-string<Model> $modelClass
 */
function models(Model|int|iterable $modelsOrIds, string $modelClass): Model|Collection
{
    if ($modelsOrIds instanceof iterable) {
        return collect($modelsOrIds)->map(fn ($id) => model($id, $modelClass));
    }

    return model($modelsOrIds, $modelClass);
}

/**
 * @param class-string<UnitEnum> $enumClass
 */
function enumFromValue(string $enumClass, string $value): UnitEnum
{
    if (! is_subclass_of($enumClass, UnitEnum::class)) {
        throw new InvalidArgumentException("'{$enumClass}' is not a valid Enum FQN.");
    }

    foreach ($enumClass::cases() as $case) {
        if ($value === $case->value ?? $case->name) {
            return $case;
        }
    }

    throw new ValueError("Invalid name/value '{$value}' for enum '{$enumClass}'");
}

function state(string $key, mixed $default = null): mixed
{
    return session()->get("state.{$key}", $default);
}

function defineState(string $key, mixed $value): void
{
    session()->put("state.{$key}", $value);
}

/**
 * Get the current project.
 */
function currentProject(): ?Project
{
    return state('project');
}

/**
 * Get the current project ID.
 */
function currentProjectId(): ?int
{
    return currentProject()?->id;
}
