<?php

use App\Models\Project;

function setFirstProjectAsCurrent(): void
{
    setCurrentProject(once(fn () => Project::withoutGlobalScopes()->findOrFail(1)));
}

/**
 * Set the current Project context for the authenticated user.
 */
function setCurrentProject(int|Project $modelOrId, bool $withTrashed = false): void
{
    $builder = Project::withoutGlobalScopes()->when($withTrashed, fn ($q) => $q->withTrashed());

    $project = $modelOrId instanceof Project ? $modelOrId : $builder->findOrFail($modelOrId);

    selectProject($project);
}

/**
 * Reset the current project.
 */
function resetCurrentProject(): void
{
    unsetCurrentProject();
}
