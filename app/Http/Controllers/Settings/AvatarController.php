<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AvatarController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:2048']]);

        $user = $request->user();
        $previous = $user->avatar;

        $user->update(['avatar' => $request->file('avatar')->store('avatars', 'public')]);

        $previous && Storage::disk('public')->delete($previous);

        return back()->with('success', __('flash.avatar_updated'));
    }
}
