<?php

use App\Models\Family;
use App\Models\Member;

/**
 * Create a basic Family for testing (without auto-creating dependencies).
 * For a Family with auto-created Project/unit_type, use createFamily() from UserHelpers.
 */
function createBasicFamily(array $attributes = []): Family
{
    return Family::factory()->create($attributes);
}

/**
 * Create a Family in a specific Project.
 */
function createFamilyInProject($project, array $attributes = []): Family
{
    return Family::factory()->create(array_merge([
        'project_id' => $project->id,
    ], $attributes));
}

/**
 * Create a Family with Members.
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
