<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationReadController
{
    /**
     * Mark a notification as read.
     */
    public function read(Request $request, Notification $notification): JsonResponse
    {
        Gate::authorize('view', $notification);

        $notification->markAsReadBy($request->user());

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Mark a notification as unread.
     */
    public function unread(Request $request, Notification $notification): JsonResponse
    {
        Gate::authorize('view', $notification);

        $notification->markAsUnreadBy($request->user());

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Mark all user notifications as read.
     */
    public function readAll(Request $request): JsonResponse
    {
        $notifications = Notification::forUser($request->user())->get();

        NotificationRead::markManyAsReadBy($notifications, $request->user());

        return response()->json([
            'success' => true,
        ]);
    }
}
