<?php

// Copilot - Pending review

/**
 * Tests for ResourceLifecycle notification service.
 *
 * Covers:
 * - Automatic notification generation for handled resources
 * - Correct target assignment (global vs project-specific)
 * - Notification payload structure (title, message, action, resource data)
 * - Special cases: auto-created resources that should NOT be notified
 * - Non-handled models that should NOT generate notifications
 * - All CRUD operations: create, update, delete, restore
 */

use App\Enums\EventType;
use App\Enums\NotificationTarget;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Family;
use App\Models\Log;
use App\Models\Media;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Plan;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Support\Facades\Auth;

uses()->group('Feature.Services.Notifications');

beforeEach(function () {
    setFirstProjectAsCurrent();
    Auth::login(Admin::find(11));
});

describe('Resource Creation Notifications', function () {
    it('creates notification for Event with correct payload and project target', function () {
        $event = Event::factory()->create([
            'project_id'   => 1,
            'creator_id'   => 11,
            'type'         => EventType::ONLINE,
            'title'        => 'Test Meeting Event',
            'description'  => 'Test description',
            'location'     => 'https://meet.example.com/test',
            'start_date'   => now(),
            'is_published' => true,
            'rsvp'         => false,
        ]);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::PROJECT)
            ->and($notification->target_id)->toBe(1)
            ->and($notification->triggered_by)->toBe(11)
            ->and($notification->data['resource'])->toBe('event')
            ->and($notification->data['resource_id'])->toBe($event->id)
            ->and($notification->data['title'])->toContain('published a new Event')
            ->and($notification->data['message'])->toContain('Test Meeting Event')
            ->and($notification->data['action'])->not->toBeNull();
    });

    it('creates notification for Family with correct payload', function () {
        $family = Family::factory()->create([
            'project_id'   => 1,
            'unit_type_id' => 1,
            'name'         => 'Test Family',
        ]);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::PROJECT)
            ->and($notification->target_id)->toBe(1)
            ->and($notification->data['resource'])->toBe('family')
            ->and($notification->data['resource_id'])->toBe($family->id)
            ->and($notification->data['title'])->toContain('added a new Family')
            ->and($notification->data['message'])->toContain('Test Family');
    });

    it('creates notification for Media with correct payload and description', function () {
        $media = Media::factory()->create([
            'project_id'  => 1,
            'owner_id'    => 11,
            'path'        => 'media/test-image.jpg',
            'file_size'   => 102400,
            'description' => 'A very long description that should be truncated in the notification message for better display',
        ]);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['resource'])->toBe('media')
            ->and($notification->data['resource_id'])->toBe($media->id)
            ->and($notification->data['title'])->toContain('uploaded a new file')
            ->and($notification->data['message'])->toContain('A very long description');
    });

    it('creates notification for Unit with correct payload including unit type', function () {
        $unit = Unit::factory()->create([
            'project_id'   => 1,
            'unit_type_id' => 1,
            'plan_item_id' => 1,
            'identifier'   => 'TEST-101',
        ]);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['resource'])->toBe('unit')
            ->and($notification->data['resource_id'])->toBe($unit->id)
            ->and($notification->data['title'])->toContain('created a new housing Unit')
            ->and($notification->data['message'])->toContain('TEST-101')
            ->and($notification->data['message'])->toContain($unit->type->name);
    });

    it('creates notification for UnitType with correct payload', function () {
        $unitType = UnitType::factory()->create([
            'project_id' => 1,
            'name'       => 'Studio Apartment',
        ]);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['resource'])->toBe('unittype')
            ->and($notification->data['resource_id'])->toBe($unitType->id)
            ->and($notification->data['title'])->toContain('created a new Unit type')
            ->and($notification->data['message'])->toContain('Studio Apartment');
    });

    it('creates GLOBAL notification for Project creation', function () {
        $project = Project::factory()->create([
            'name'         => 'New Test Project',
            'description'  => 'Test description',
            'organization' => 'Test Org',
            'active'       => true,
        ]);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::GLOBAL)
            ->and($notification->target_id)->toBeNull()
            ->and($notification->data['resource'])->toBe('project')
            ->and($notification->data['resource_id'])->toBe($project->id)
            ->and($notification->data['title'])->toContain('added a new Project')
            ->and($notification->data['message'])->toContain('New Test Project')
            ->and($notification->data['action'])->toContain('projects');
    });
});

describe('Auto-created Resources Should NOT Notify', function () {
    it('does NOT create notification for auto-created Lottery when creating Project', function () {
        $initialCount = Notification::count();

        $project = Project::factory()->create([
            'name'         => 'New Test Project',
            'description'  => 'Test description',
            'organization' => 'Test Org',
            'active'       => true,
        ]);

        // Should have exactly 1 new notification (for Project, not for auto-created Lottery/Plan)
        $newNotifications = Notification::where('id', '>', $initialCount)->get();

        expect($newNotifications)->toHaveCount(1)
            ->and($newNotifications[0]->data['resource'])->toBe('project');
    });

    it('does NOT create notification for auto-created PlanItem when creating Unit', function () {
        $initialCount = Notification::count();

        $unit = Unit::factory()->create([
            'project_id'   => 1,
            'unit_type_id' => 1,
            'plan_item_id' => 1,
            'identifier'   => 'AUTO-TEST-102',
        ]);

        // Should have exactly 1 new notification (for Unit only)
        $newNotifications = Notification::where('id', '>', $initialCount)->get();

        expect($newNotifications)->toHaveCount(1)
            ->and($newNotifications[0]->data['resource'])->toBe('unit');
    });
});

describe('Non-handled Models Should NOT Notify', function () {
    it('does NOT create notification when creating a Member', function () {
        $initialCount = Notification::count();

        Member::factory()->create([
            'email'     => 'newmember@test.com',
            'firstname' => 'Test',
            'lastname'  => 'Member',
            'family_id' => 2,
        ]);

        expect(Notification::count())->toBe($initialCount);
    });

    it('does NOT create notification when updating a Member', function () {
        $initialCount = Notification::count();

        $member = Member::find(102);
        $member->update(['firstname' => 'Updated Name']);

        expect(Notification::count())->toBe($initialCount);
    });

    it('does NOT create notification when creating a Log', function () {
        $initialCount = Notification::count();

        Log::factory()->create([
            'event'      => 'Test Action',
            'project_id' => 1,
            'creator_id' => 11,
        ]);

        expect(Notification::count())->toBe($initialCount);
    });
});

describe('Resource Update Notifications', function () {
    it('creates PROJECT-SCOPED notification for Event update', function () {
        $event = Event::find(1); // From universe.sql
        $event->update(['title' => 'Updated Event Title']);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::PROJECT)
            ->and($notification->target_id)->toBe($event->project_id)
            ->and($notification->data['resource'])->toBe('event')
            ->and($notification->data['title'])->toContain('updated an Event')
            ->and($notification->data['message'])->toContain('Updated Event Title');
    });

    it('creates PROJECT-SCOPED notification for Project update (not global)', function () {
        $project = Project::withoutGlobalScopes()->find(1);
        $project->update(['name' => 'Updated Project Name']);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::PROJECT)
            ->and($notification->target_id)->toBe(1)
            ->and($notification->data['resource'])->toBe('project')
            ->and($notification->data['title'])->toContain('updated a Project')
            ->and($notification->data['message'])->toContain('Updated Project Name');
    });

    it('creates notification for UnitType update', function () {
        $unitType = UnitType::find(1);
        $unitType->update(['name' => 'Renovated Studio']);

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['resource'])->toBe('unittype')
            ->and($notification->data['title'])->toContain('updated a Unit type')
            ->and($notification->data['message'])->toContain('Renovated Studio');
    });
});

describe('Resource Delete Notifications', function () {
    it('creates notification for Event soft delete', function () {
        $event = Event::find(1);
        $event->delete();

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::PROJECT)
            ->and($notification->data['resource'])->toBe('event')
            ->and($notification->data['title'])->toContain('cancelled an Event')
            ->and($notification->data['message'])->toContain($event->title);
    });

    it('creates GLOBAL notification for Project soft delete', function () {
        $project = Project::withoutGlobalScopes()->find(4); // Use Project 4 (inactive)
        $project->delete();

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::GLOBAL)
            ->and($notification->target_id)->toBeNull()
            ->and($notification->data['resource'])->toBe('project')
            ->and($notification->data['title'])->toContain('deleted a Project');
    });

    it('creates notification for Family soft delete', function () {
        $family = Family::find(1);
        $familyName = $family->name;
        $family->delete();

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['resource'])->toBe('family')
            ->and($notification->data['title'])->toContain('removed a Family')
            ->and($notification->data['message'])->toContain($familyName);
    });
});

describe('Resource Restore Notifications', function () {
    it('creates notification for Event restore', function () {
        $event = Event::find(1);
        $eventTitle = $event->title;
        $event->delete();
        Notification::query()->delete(); // Clear delete notification

        $event->restore();

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::PROJECT)
            ->and($notification->data['resource'])->toBe('event')
            ->and($notification->data['title'])->toContain('Event')
            ->and($notification->data['message'])->toContain($eventTitle);
    });

    it('creates GLOBAL notification for Project restore', function () {
        $project = Project::withoutGlobalScopes()->find(4);
        $project->delete();
        Notification::query()->delete();

        $project->restore();

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->target)->toBe(NotificationTarget::GLOBAL)
            ->and($notification->target_id)->toBeNull()
            ->and($notification->data['resource'])->toBe('project')
            ->and($notification->data['title'])->toContain('Project');
    });

    it('creates notification for Unit restore', function () {
        $unit = Unit::find(1);
        $unitIdentifier = $unit->identifier;
        $unit->delete();
        Notification::query()->delete(); // Clear delete notification

        $unit->restore();

        $notification = Notification::latest()->first();

        expect($notification)->not->toBeNull()
            ->and($notification->data['resource'])->toBe('unit')
            ->and($notification->data['title'])->toContain('Unit')
            ->and($notification->data['message'])->toContain($unitIdentifier);
    });
});

describe('Notification Triggered By', function () {
    it('sets triggered_by to authenticated user', function () {
        $admin = Admin::find(11); // Use Admin #11 from universe.sql

        Event::factory()->create([
            'project_id'   => 1,
            'creator_id'   => 11,
            'type'         => EventType::ONLINE,
            'title'        => 'Another Test Event',
            'description'  => 'Test',
            'location'     => 'https://meet.example.com/another',
            'start_date'   => now(),
            'is_published' => true,
            'rsvp'         => false,
        ]);

        $notification = Notification::latest()->first();

        expect($notification->triggered_by)->toBe(11);
    });

    it('uses app name when no user is authenticated', function () {
        Auth::logout();

        Event::factory()->create([
            'project_id'   => 1,
            'creator_id'   => 11,
            'type'         => EventType::ONLINE,
            'title'        => 'System Generated Event',
            'description'  => 'Test',
            'location'     => 'https://meet.example.com/system',
            'start_date'   => now(),
            'is_published' => true,
            'rsvp'         => false,
        ]);

        $notification = Notification::latest()->first();

        expect($notification->triggered_by)->toBeNull()
            ->and($notification->data['title'])->toContain(config('app.name'));
    });
});
