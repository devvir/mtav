<?php

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\AvatarUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AvatarController
{
    /**
     * Update the user's avatar.
     */
    public function update(AvatarUpdateRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->avatar && Storage::disk('public')->delete($user->avatar);

        $user->update([
            'avatar' => $request->avatar->store('avatars', 'public'),
        ]);

        return response()->json([
            'message' => __('Avatar updated successfully!'),
            'avatar_url' => asset('storage/' . $path),
        ]);
    }
}
