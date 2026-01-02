<?php

// Copilot - Pending review

use App\Enums\NotificationTarget;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\User;

describe('NotificationResource', function () {
    it('transforms notification with basic fields', function () {
        $notification = Notification::find(1);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource)->toHaveKeys(['id', 'data', 'target', 'target_id', 'created_at'])
            ->and($resource['id'])->toBe(1)
            ->and($resource['data'])->toBeArray()
            ->and($resource['target'])->toBe(NotificationTarget::PRIVATE->value)
            ->and($resource['target_id'])->toBeInt();
    });

    it('includes is_read as true when read attribute is set to true', function () {
        $notification = Notification::find(1);
        $notification->setAttribute('read', true);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource)->toHaveKey('is_read')
            ->and($resource['is_read'])->toBeTrue();
    });

    it('includes is_read as false when read attribute is set to false', function () {
        $notification = Notification::find(1);
        $notification->setAttribute('read', false);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource)->toHaveKey('is_read')
            ->and($resource['is_read'])->toBeFalse();
    });

    it('excludes is_read when read attribute is not set', function () {
        $notification = Notification::find(1);
        // Do NOT set read attribute

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource)->not->toHaveKey('is_read');
    });

    it('works with NotificationCollection withReadState method', function () {
        $user = User::find(102);
        $notifications = Notification::forUser($user)->limit(3)->get();

        // Mark first as read
        $notifications->first()->markAsReadBy($user);

        // Load read state using custom collection method
        $notifications->withReadState($user);

        $resources = NotificationResource::collection($notifications)->resolve();

        expect($resources)->toHaveCount(3)
            ->and($resources[0]['is_read'])->toBeTrue()
            ->and($resources[1]['is_read'])->toBeFalse()
            ->and($resources[2]['is_read'])->toBeFalse();
    });

    it('transforms data field correctly', function () {
        $notification = Notification::find(1);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource['data'])->toBeArray()
            ->and($resource['data'])->toHaveKey('title')
            ->and($resource['data'])->toHaveKey('message');
    });

    it('includes timestamps', function () {
        $notification = Notification::find(1);

        $resource = NotificationResource::make($notification)->resolve();

        expect($resource)->toHaveKey('created_at')
            ->and($resource['created_at'])->toBeInstanceOf(\Carbon\Carbon::class);
    });
});
