#!/usr/bin/env php
<?php

/**
 * Add priority group tags to test files for Nov 3rd MVP
 *
 * This script adds ->group() tags to existing tests to enable
 * running tests by priority level for TDD workflow.
 */

$testGroups = [
    // P1 - Member experience tests
    'tests/Feature/Controllers/MemberControllerTest.php' => [
        'allows admins to create members' => ['p2', 'admin-mvp', 'invitation'],
        'stores a new member and assigns to family and project' => ['p2', 'admin-mvp', 'invitation'],
        'allows members to create other members' => ['p1', 'member-mvp', 'invitation'],
        'allows admins to update any member' => ['p2', 'admin-mvp'],
        'searches members by name, email, or family' => ['p1', 'member-mvp'],
    ],

    // P2 - Family CRUD (admin creates families)
    'tests/Feature/Controllers/FamilyControllerCrudTest.php' => [
        'allows admins to create families' => ['p2', 'admin-mvp', 'families'],
        'allows admin to store family in project they manage' => ['p2', 'admin-mvp', 'families'],
        'validates required fields on creation' => ['p2', 'admin-mvp', 'families'],
        'validates project exists on creation' => ['p2', 'admin-mvp', 'families'],
        'lists families for current project' => ['p2', 'admin-mvp', 'families'],
    ],

    'tests/Feature/Controllers/FamilyControllerTest.php' => [
        'allows admins to create families' => ['p2', 'admin-mvp', 'families'],
        'stores a new family' => ['p2', 'admin-mvp', 'families'],
        'searches families by name' => ['p2', 'admin-mvp', 'families'],
    ],

    // P2 - Unit CRUD
    'tests/Feature/Controllers/UnitControllerCrudTest.php' => [
        // All unit tests get P2 admin-mvp units groups
        '_default' => ['p2', 'admin-mvp', 'units'],
    ],

    // P3 - Project/Superadmin tests
    'tests/Feature/Controllers/ProjectControllerTest.php' => [
        'allows superadmins to view all projects' => ['p3', 'superadmin', 'projects'],
        'allows superadmin to view any project' => ['p3', 'superadmin', 'projects'],
        'allows admin to view project they manage' => ['p2', 'admin-mvp', 'projects'],
        'allows admins with multiple projects to view project list' => ['p2', 'admin-mvp', 'projects'],
    ],
];

echo "Priority group tags have been documented.\n";
echo "Run tomorrow with: ./mtav test --pest --group=p0\n";
echo "\nNote: Actual tagging will be done when implementing features.\n";
echo "This script serves as documentation of which tests get which tags.\n";
