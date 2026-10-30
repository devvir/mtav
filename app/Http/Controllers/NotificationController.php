<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $notifications = Notification::for($user)
            ->when($request->boolean('unread'), fn ($q) => $q->unreadBy($user))
            ->paginate(30)
            ->withQueryString();

        return inertia('Notifications/Index', [
            'notifications' => NotificationResource::collection($notifications),
            'filters'       => ['unread' => $request->boolean('unread')],
        ]);
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        $this->authorize('view', $notification);

        $notification->markAsReadBy($request->user());

        // Opening a notification: read, and straight to what it is about
        $url = $notification->data['url'] ?? null;

        return $url ? redirect($url) : back();
    }

    public function unread(Request $request, Notification $notification): RedirectResponse
    {
        $this->authorize('view', $notification);

        $notification->markAsUnreadBy($request->user());

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        $unread = Notification::for($user)->unreadBy($user)->pluck('id');

        DB::table('notification_reads')->insertOrIgnore(
            $unread->map(fn (int $id) => ['notification_id' => $id, 'user_id' => $user->id, 'read_at' => now()])->all()
        );

        return back()->with('success', __('flash.notifications_read'));
    }
}
