<?php

use App\Models\Project;

/**
 * Set the current Project context for the authenticated user.
 */
function setCurrentProject(int|Project $modelOrId, bool $withTrashed = false): void
{
    $builder = Project::withoutGlobalScopes()->when($withTrashed, fn ($q) => $q->withTrashed());

    $project = $modelOrId instanceof Project ? $modelOrId : $builder->findOrFail($modelOrId);

    defineState('project', $project);
}

/**
 * Reset the current project.
 */
function resetCurrentProject(): void
{
    defineState('project', null);
}
