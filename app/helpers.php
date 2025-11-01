<?php

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * For a model or model id, return the model.
 *
 * @throws ModelNotFoundException if $modelOrId is an invalid id for $modelClass
 */
function model(Model|int $modelOrId, string $modelClass): Model
{
    return $modelOrId instanceof Model
        ? $modelOrId
        : $modelClass::findOrFail($modelOrId);
}

function models(Model|int|iterable $modelsOrIds, string $modelClass): Model|Collection
{
    if ($modelsOrIds instanceof iterable) {
        return collect($modelsOrIds)->map(fn ($id) => model($id, $modelClass));
    }

    return model($modelsOrIds, $modelClass);
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

function setState(string $key, mixed $value): void
{
    session()->put("state.$key", $value);
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
