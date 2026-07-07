<?php

/**
 * Tests for the NotificationReadController routes (read / unread / read-all).
 *
 * Universe notifications: User #102 sees their private ones (#1, #2) plus the
 * Project #1 ones created after their registration. Private notifications of
 * other users must not be accessible (403 renders as a redirect to the Dashboard).
 */

use App\Models\Notification;
use App\Models\User;

uses()->group('Feature.Notifications');

function privateNotificationFor(int $userId): Notification
{
    return Notification::private($userId)->firstOrFail();
}

describe('Marking a notification as read/unread', function () {
    it('marks an own private notification as read, and unread again', function () {
        $user = User::find(102);
        $notification = privateNotificationFor(102);
        $this->actingAs($user);

        $this->post(route('notifications.read', $notification))
            ->assertOk()->assertJson(['success' => true]);
        expect($notification->fresh()->isReadBy($user))->toBeTrue();

        $this->post(route('notifications.unread', $notification))
            ->assertOk()->assertJson(['success' => true]);
        expect($notification->fresh()->isReadBy($user))->toBeFalse();
    });

    it('marks a project notification as read for a project member', function () {
        $user = User::find(102); // Member of Project #1
        $notification = Notification::project(1)->firstOrFail();
        $this->actingAs($user);

        $this->post(route('notifications.read', $notification))->assertOk();

        expect($notification->fresh()->isReadBy($user))->toBeTrue();
    });

    it('denies marking another user\'s private notification as read', function () {
        $user = User::find(102);
        $foreignNotification = privateNotificationFor(136); // Member #136, Project #2
        $this->actingAs($user);

        $this->post(route('notifications.read', $foreignNotification))
            ->assertRedirect(route('dashboard'));

        expect($foreignNotification->fresh()->isReadBy($user))->toBeFalse();
    });

    it('requires authentication', function () {
        $notification = privateNotificationFor(102);

        $this->post(route('notifications.read', $notification))
            ->assertRedirect(route('login'));
    });
});

describe('Marking all notifications as read', function () {
    it('marks every notification the user can see as read', function () {
        $user = User::find(102);
        $this->actingAs($user);

        // Notifications visible to the user (the sinceRegistration global scope
        // hides those older than the user's registration).
        $visibleCount = $user->notifications()->count();

        expect($visibleCount)->toBeGreaterThan(0)
            ->and($user->unreadNotifications()->count())->toBe($visibleCount);

        $this->post(route('notifications.readAll'))
            ->assertOk()->assertJson(['success' => true]);

        expect($user->unreadNotifications()->count())->toBe(0)
            ->and($user->readNotifications()->count())->toBe($visibleCount);
    });

    it('does not mark other users\' private notifications as read', function () {
        // Fetch the foreign notification and user before authenticating (global
        // scopes would hide them from User #102).
        $otherUser = User::find(136);
        $foreign = privateNotificationFor(136);

        $this->actingAs(User::find(102));
        $this->post(route('notifications.readAll'))->assertOk();

        $foreignFresh = Notification::withoutGlobalScopes()->find($foreign->id);
        expect($foreignFresh->isReadBy($otherUser))->toBeFalse();
    });
});
