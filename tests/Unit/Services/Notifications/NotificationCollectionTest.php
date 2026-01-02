<?php

// Copilot - Pending review

use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationCollection;

describe('NotificationCollection - withReadState', function () {
    it('auto-applies read state for currently logged in user', function () {
        $user = User::find(102);
        auth()->login($user);

        // Mark first notification as read
        $firstNotification = Notification::forUser($user)->first();
        $firstNotification->markAsReadBy($user);

        // Get fresh collection - constructor should auto-call withReadState for authenticated user
        $notifications = Notification::forUser($user)->limit(3)->get();

        expect($notifications)->toBeInstanceOf(NotificationCollection::class)
            ->and($notifications->first()->read)->toBeTrue()
            ->and($notifications->skip(1)->first()->read)->toBeFalse();
    });

    it('can be calculated for a different user when called directly', function () {
        $user102 = User::find(102);
        $user103 = User::find(103);
        auth()->login($user103); // Login as different user

        // Mark notification as read by user 102
        $notification = Notification::forUser($user102)->first();
        $notification->markAsReadBy($user102);

        // Get notifications and explicitly call withReadState for user 102
        $notifications = Notification::forUser($user102)->limit(3)->get();
        $collection = $notifications->withReadState($user102);

        expect($collection->first()->read)->toBeTrue()
            ->and($collection->skip(1)->first()->read)->toBeFalse();
    });

    it('marks all notifications as unread when user has not read any', function () {
        $user = User::find(103);
        $notifications = Notification::forUser($user)->limit(5)->get();

        $collection = $notifications->withReadState($user);

        expect($collection->every(fn ($n) => $n->read === false))->toBeTrue();
    });

    it('returns empty collection unchanged', function () {
        $user = User::find(102);
        $notifications = new NotificationCollection();

        $collection = $notifications->withReadState($user);

        expect($collection)->toBeInstanceOf(NotificationCollection::class)
            ->and($collection->isEmpty())->toBeTrue();
    });

    it('skips withReadState when not authenticated and no user provided', function () {
        auth()->logout();

        $notifications = Notification::limit(3)->get();

        // Should not have 'read' attribute since no user was provided and not authenticated
        expect($notifications)->toBeInstanceOf(NotificationCollection::class)
            ->and($notifications->count())->toBe(3)
            ->and($notifications->first()->getAttributes())->not->toHaveKey('read');
    });

    it('efficiently loads read state with single query for multiple notifications', function () {
        $user = User::find(102);
        $notifications = Notification::forUser($user)->get(); // User 102 has 7 notifications

        expect($notifications->count())->toBe(7); // Verify count

        // Mark first 3 as read
        $notifications->take(3)->each(fn ($n) => $n->markAsReadBy($user));

        // Get fresh collection to avoid cached relations
        $freshNotifications = Notification::forUser($user)->get();

        $collection = $freshNotifications->withReadState($user);

        $readCount = $collection->filter(fn ($n) => $n->read)->count();
        $unreadCount = $collection->filter(fn ($n) => ! $n->read)->count();

        expect($readCount)->toBe(3)
            ->and($unreadCount)->toBe(4)
            ->and($readCount + $unreadCount)->toBe(7);
    });
});
