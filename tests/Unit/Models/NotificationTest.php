<?php

use App\Enums\NotificationTarget;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

describe('Notification Model Relations', function () {
    it('belongs to many users through readBy relation', function () {
        $notification = Notification::find(1);
        $user = User::find(102);

        $notification->markAsReadBy($user);

        expect($notification->readBy()->get())
            ->toBeInstanceOf(Collection::class)
            ->and($notification->readBy->pluck('id'))->toContain($user->id);
    });
});

describe('Notification Model - Read/Unread Methods', function () {
    it('can check if a user has read the notification', function () {
        $notification = Notification::find(1);
        $user = User::find(102);

        expect($notification->isReadBy($user))->toBeFalse();

        $notification->markAsReadBy($user);

        expect($notification->isReadBy($user))->toBeTrue();
    });

    it('can mark notification as read by a user', function () {
        $notification = Notification::find(2);
        $user = User::find(102);

        $notification->markAsReadBy($user);

        expect($notification->readBy->pluck('id'))->toContain($user->id);
    });

    it('does not duplicate read status when marking as read twice', function () {
        $notification = Notification::find(3);
        $user = User::find(103);

        $notification->markAsReadBy($user);
        $notification->markAsReadBy($user);

        expect($notification->readBy()->count())->toBe(1);
    });

    it('can mark notification as unread by a user', function () {
        $notification = Notification::find(4);
        $user = User::find(103);

        $notification->markAsReadBy($user);
        expect($notification->isReadBy($user))->toBeTrue();

        $notification->markAsUnreadBy($user);
        expect($notification->fresh()->isReadBy($user))->toBeFalse();
    });

    it('checks read status from loaded relation when available', function () {
        $notification = Notification::with('readBy')->find(5);
        $user = User::find(136);

        $notification->markAsReadBy($user);
        $notification->load('readBy');

        // Should use loaded relation, not query database
        expect($notification->isReadBy($user))->toBeTrue();
    });
});

describe('Notification Model - Scope: global', function () {
    it('filters global notifications', function () {
        $global = Notification::global()->get();

        expect($global->count())->toBe(2)
            ->and($global->every(fn ($n) => $n->target === NotificationTarget::GLOBAL))->toBeTrue();
    });
});

describe('Notification Model - Scope: project', function () {
    it('filters all project notifications', function () {
        $projectNotifications = Notification::project()->get();

        expect($projectNotifications->count())->toBe(10)
            ->and($projectNotifications->every(fn ($n) => $n->target === NotificationTarget::PROJECT))->toBeTrue();
    });

    it('filters project notifications by project ID', function () {
        $project1Notifications = Notification::project(1)->get();

        expect($project1Notifications->count())->toBe(5)
            ->and($project1Notifications->every(fn ($n) => $n->target_id === 1))->toBeTrue();
    });

    it('filters project notifications by Project model instance', function () {
        $project = Project::find(2);
        $project2Notifications = Notification::project($project)->get();

        expect($project2Notifications->count())->toBe(5)
            ->and($project2Notifications->every(fn ($n) => $n->target_id === 2))->toBeTrue();
    });
});

describe('Notification Model - Scope: private', function () {
    it('filters all private (user) notifications', function () {
        $privateNotifications = Notification::private()->get();

        expect($privateNotifications->count())->toBe(8)
            ->and($privateNotifications->every(fn ($n) => $n->target === NotificationTarget::PRIVATE))->toBeTrue();
    });

    it('filters private notifications by user ID', function () {
        $user102Notifications = Notification::private(102)->get();

        expect($user102Notifications->count())->toBe(2)
            ->and($user102Notifications->every(fn ($n) => $n->target_id === 102))->toBeTrue();
    });

    it('filters private notifications by User model instance', function () {
        $user = User::find(136);
        $user136Notifications = Notification::private($user)->get();

        expect($user136Notifications->count())->toBe(2)
            ->and($user136Notifications->every(fn ($n) => $n->target_id === 136))->toBeTrue();
    });
});

describe('Notification Model - Data Casting', function () {
    it('casts data column to array', function () {
        $notification = Notification::find(1);

        expect($notification->data)->toBeArray()
            ->and($notification->data)->toHaveKey('title')
            ->and($notification->data)->toHaveKey('message');
    });
});
