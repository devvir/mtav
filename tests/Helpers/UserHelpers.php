<?php

use App\Models\Admin;
use App\Models\Member;
use App\Models\User;

/**
 * Create a superadmin user for testing.
 */
function createSuperAdmin(array $attributes = []): Admin
{
    $id = $attributes['id'] ?? 999;
    config(['auth.superadmins' => array_merge(config('auth.superadmins', []), [$id])]);

    return Admin::factory()->create(array_merge(['id' => $id], $attributes));
}

/**
 * Create a regular admin user for testing.
 */
function createAdmin(array $attributes = []): Admin
{
    return Admin::factory()->create($attributes);
}

/**
 * Create a member user for testing.
 */
function createMember(array $attributes = []): Member
{
    return Member::factory()->create($attributes);
}

/**
 * Create an admin assigned to specific projects.
 */
function createAdminWithProjects(array $projects, array $attributes = []): Admin
{
    $admin = createAdmin($attributes);

    foreach ($projects as $project) {
        $project->addAdmin($admin);
    }

    return $admin->fresh();
}

/**
 * Create a member assigned to a specific project.
 */
function createMemberInProject($project, $family = null, array $attributes = []): Member
{
    $family = $family ?? \App\Models\Family::factory()->create(['project_id' => $project->id]);

    $member = Member::factory()->create(array_merge([
        'family_id' => $family->id,
    ], $attributes));

    $member->joinProject($project);

    return $member->fresh();
}
