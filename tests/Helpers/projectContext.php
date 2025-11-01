<?php

use App\Models\Project;

/**
 * Set the current project context for the authenticated user.
 */
function setCurrentProject(int|Project $modelOrId, bool $withTrashed = false): void
{
    $builder = $withTrashed ? Project::withTrashed() : Project::query();
    $project = is_int($modelOrId) ? $builder->findOrFail($modelOrId) : $modelOrId;

    setState('project', $project);
}

/**
 * Reset the current project.
 */
function resetCurrentProject(): void
{
    setState('project', null);
}
