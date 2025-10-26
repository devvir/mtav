<?php

use App\Models\Family;
use App\Models\Member;

/**
 * Create a family for testing.
 */
function createFamily(array $attributes = []): Family
{
    return Family::factory()->create($attributes);
}

/**
 * Create a family in a specific project.
 */
function createFamilyInProject($project, array $attributes = []): Family
{
    return Family::factory()->create(array_merge([
        'project_id' => $project->id,
    ], $attributes));
}

/**
 * Create a family with members.
 */
function createFamilyWithMembers($project = null, int $memberCount = 3, array $attributes = []): Family
{
    $project = $project ?? createProject();
    $family = createFamilyInProject($project, $attributes);

    for ($i = 0; $i < $memberCount; $i++) {
        createMemberInProject($project, $family);
    }

    return $family->fresh();
}
