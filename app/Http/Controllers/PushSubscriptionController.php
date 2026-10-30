<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A browser or device someone allows to receive notifications, or no longer: each is the
 * person's own (one taken over by someone else signing in there becomes theirs).
 */
class PushSubscriptionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'endpoint'    => ['required', 'url:https', 'max:700'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth'   => ['required', 'string', 'max:255'],
        ]);

        PushSubscription::firstOrNew(['endpoint' => $data['endpoint']])->forceFill([
            'user_id'    => $request->user()->id,
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
        ])->save();

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->pushSubscriptions()->where('endpoint', $request->string('endpoint'))->delete();

        return back();
    }
}
