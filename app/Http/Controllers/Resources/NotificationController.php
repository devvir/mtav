<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\FilteredIndexRequest;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * List all notifications accessible to the current user.
     */
    public function index(FilteredIndexRequest $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->when($request->q, fn ($q, $search) => $q->search($search));

        return inertia('Notifications', [
            'notifications' => Inertia::defer(fn () => $notifications->paginate(30))->deepMerge(),
            'q'             => $request->q ?? '',
        ]);
    }
}
