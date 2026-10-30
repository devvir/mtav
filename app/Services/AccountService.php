<?php

namespace App\Services;

use App\Models\Invitation;
use App\Models\JoinRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * An account's end. Deleting is bookkeeping: the account goes, and its email may sign up
 * again. Blocking keeps it, closed, its email taken, so that it never joins again.
 */
class AccountService
{
    /**
     * @throws ValidationException when it is the only admin of a project
     */
    public function ensureCanGo(User $user, string $errorKey = 'confirmation'): void
    {
        $alone = $user->projects()->wherePivot('is_admin', true)->get()
            ->filter(fn ($project) => $project->admins()->count() === 1);

        if ($alone->isNotEmpty()) {
            throw ValidationException::withMessages([$errorKey => __('validation.only_admin_of', [
                'projects' => $alone->pluck('name')->join(', '),
            ])]);
        }
    }

    public function delete(User $user): void
    {
        $avatar = $user->avatar;
        $user->delete();
        $avatar && Storage::disk('public')->delete($avatar);
    }

    /** Out of every project at once, every session ended; nothing waiting for them stays */
    public function block(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->forceFill(['blocked_at' => now(), 'remember_token' => Str::random(60)])->save();
            $user->memberships()->get()->each->delete();
            $user->unsetRelation('memberships');
            Invitation::pending()->where('email', $user->email)->delete();
            JoinRequest::where('user_id', $user->id)->delete();
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
        });
    }

    public function unblock(User $user): void
    {
        $user->forceFill(['blocked_at' => null])->save();
    }
}
