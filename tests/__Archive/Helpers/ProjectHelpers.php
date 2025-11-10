<?php

use App\Models\Project;

/**
 * Create a Project for testing.
 */
function createProject(array $attributes = []): Project
{
    return Project::factory()->create($attributes);
}

/**
 * Create multiple projects.
 */
function createProjects(int $count = 3): \Illuminate\Support\Collection
{
    return Project::factory()->count($count)->create();
}

/**
 * Create a Project with an Admin assigned to it.
 */
function createProjectWithAdmin($admin = null, array $attributes = []): Project
{
    $project = createProject($attributes);
    $admin = $admin ?? createAdmin();

    $project->addAdmin($admin);

    return $project->fresh();
}

/**
 * Create a Project with Families and Members.
 */
function createProjectWithFamilies(int $familyCount = 3, int $membersPerFamily = 2): Project
{
    $project = createProject();

    for ($i = 0; $i < $familyCount; $i++) {
        $family = \App\Models\Family::factory()->create(['project_id' => $project->id]);

        for ($j = 0; $j < $membersPerFamily; $j++) {
            createMemberInProject($project, $family);
        }
    }

    return $project->fresh();
}
