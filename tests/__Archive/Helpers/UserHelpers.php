<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\User;

/**
 * Create a Superadmin user for testing.
 */
function createSuperAdmin(bool $asUser = false): Admin|User
{
    $email = 'superadmin@example.com';

    config(['auth.superadmins' => [$email]]);

    $admin = Admin::factory()->create(['email' => $email]);

    return $asUser ? User::firstWhere('email', $email) : $admin;
}

/**
 * Create a regular Admin user for testing.
 */
function createAdmin(array $attributes = [], bool $asUser = false): Admin|User
{
    $admin = Admin::factory()->create($attributes);

    return $asUser ? User::find($admin->id) : $admin;
}

/**
 * Create a Member user for testing.
 * Requires a family_id to be provided in attributes.
 */
function createMember(array $attributes = [], bool $asUser = false): Member|User
{
    // If no family_id provided, find or create one
    if (!isset($attributes['family_id'])) {
        $family = \App\Models\Family::first();

        if (!$family) {
            // Need to create a full hierarchy: Project -> UnitType -> Family
            $project = \App\Models\Project::factory()->create();
            $unitType = \App\Models\UnitType::factory()->create([
                'project_id' => $project->id,
                'name'       => 'Test Type',
            ]);
            $family = \App\Models\Family::factory()->create([
                'project_id'   => $project->id,
                'unit_type_id' => $unitType->id,
            ]);
        }

        $attributes['family_id'] = $family->id;
    }

    $member = Member::factory()->create($attributes);

    return $asUser ? User::find($member->id) : $member;
}

/**
 * Create an Admin assigned to specific Projects.
 */
function createAdminWithProjects(array $projects, array $attributes = [], bool $asUser = false): Admin|User
{
    $admin = createAdmin($attributes);

    foreach ($projects as $project) {
        $project->addAdmin($admin);
    }

    return $asUser ? User::find($admin->id) : $admin->fresh();
}

/**
 * Create a Member assigned to a specific Project.
 */
function createMemberInProject($project, $family = null, array $attributes = []): Member
{
    if (!$family) {
        // Find or create a Family in this Project
        $family = \App\Models\Family::where('project_id', $project->id)->first();

        if (!$family) {
            // Create a unit type and Family for this Project
            $unitType = \App\Models\UnitType::factory()->create([
                'project_id' => $project->id,
                'name'       => 'Test Type',
            ]);
            $family = \App\Models\Family::factory()->create([
                'project_id'   => $project->id,
                'unit_type_id' => $unitType->id,
            ]);
        }
    }

    $member = Member::factory()->create(array_merge([
        'family_id' => $family->id,
    ], $attributes));

    $member->joinProject($project);

    return $member->fresh();
}

/**
 * Create a Family for testing.
 * Will create necessary parent records (Project, UnitType) if not provided.
 */
function createFamily(array $attributes = []): \App\Models\Family
{
    if (!isset($attributes['project_id'])) {
        $project = \App\Models\Project::first() ?? \App\Models\Project::factory()->create();
        $attributes['project_id'] = $project->id;
    }

    if (!isset($attributes['unit_type_id'])) {
        $unitType = \App\Models\UnitType::where('project_id', $attributes['project_id'])->first();

        if (!$unitType) {
            $unitType = \App\Models\UnitType::factory()->create([
                'project_id' => $attributes['project_id'],
                'name'       => 'Test Type',
            ]);
        }

        $attributes['unit_type_id'] = $unitType->id;
    }

    return \App\Models\Family::factory()->create($attributes);
}
