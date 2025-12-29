<?php

use App\Models\Admin;
use App\Models\Event;
use App\Models\Project;

describe('Admin Model', function () {
    it('can determine if they manage a Project', function () {
        $admin = Admin::find(11); // Admin #11 manages Project #1
        $project = Project::find(1);

        expect($admin->manages($project))->toBeTrue();
    });

    it('does not manage Projects they are not assigned to', function () {
        // Ensure the admin is not a superadmin by setting an empty superadmin list
        config(['auth.superadmins' => []]);

        $admin = Admin::find(11); // Admin #11 manages only Project #1
        $project = Project::find(2); // Project #2

        expect($admin->manages($project))->toBeFalse();
    });

    it('superadmins manage all Projects regardless of assignment', function () {
        config(['auth.superadmins' => ['superadmin1@example.com']]);
        $superadmin = Admin::find(1); // Superadmin #1 from universe
        $project = Project::find(2);

        expect($superadmin->manages($project))->toBeTrue();
    });

    it('has projects relationship', function () {
        $admin = Admin::find(12); // Admin #12 manages Projects #2, #3

        expect($admin->projects)->toHaveCount(2);
    });

    it('manages only active projects', function () {
        $admin = Admin::find(13); // Admin with inactive assignment in Project 2

        // Should only return projects where pivot.active = true
        expect($admin->projects->pluck('id'))->toContain(3) // Active in Project 3
            ->and($admin->projects->pluck('id'))->toContain(4) // Active in Project 4
            ->and($admin->projects->pluck('id'))->not->toContain(2); // Inactive in Project 2
    });

    it('identifies multi-project admins correctly', function () {
        $singleProjectAdmin = Admin::find(11); // Manages only Project 1
        $multiProjectAdmin = Admin::find(12); // Manages Projects 2 and 3

        expect($singleProjectAdmin->projects->count())->toBe(1)
            ->and($multiProjectAdmin->projects->count())->toBeGreaterThan(1);
    });

    it('has many created events', function () {
        $admin = Admin::find(11);
        $events = $admin->events;

        expect($events)->toHaveCount(5); // Admin 11 created all 5 events in Project 1

        expect($events->every(function ($event) {
            return $event instanceof Event && $event->creator_id === 11;
        }))->toBeTrue();
    });

    it('has upcoming created events via relation', function () {
        $admin = Admin::find(11);

        $upcomingEvents = $admin->upcomingEvents;
        expect($upcomingEvents->every(function ($event) {
            return is_null($event->start_date) || $event->start_date > now();
        }))->toBeTrue();
    });

    it('has search scope that searches by name', function () {
        $admin = Admin::find(11);

        $results = Admin::search($admin->firstname)->get();

        expect($results->pluck('id'))->toContain(11);
    });
});
