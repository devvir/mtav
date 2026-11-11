<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Invitation;
use App\Models\JoinRequest;
use App\Models\SupportInvitation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * An account's beginning from a link, and its end. Deleting is bookkeeping: the account goes,
 * and its email may sign up again. Blocking keeps it, closed, its email taken, so that it never
 * joins again.
 */
class AccountService
{
    /**
     * An account opened from a link mailed to its address (an invitation), signed in at once:
     * following the link proved the address. What the link grants (a role, the address
     * confirmed) is set with it, and what must happen with it ($with) in the same transaction.
     *
     * @param  array{firstname: string, lastname?: ?string, password: string}  $person  What they chose
     * @param  array<string, mixed>  $granted  Attributes nobody fills in from a form
     * @param  ?callable(User): void  $with
     *
     * @throws UniqueConstraintViolationException when another account took the address meanwhile
     */
    public function open(array $person, string $email, array $granted = [], ?callable $with = null): User
    {
        $open = function () use ($person, $email, $granted, $with) {
            $user = (new User())->forceFill([
                'firstname' => $person['firstname'],
                'lastname'  => $person['lastname'] ?? null,
                'password'  => $person['password'],
                'email'     => $email,
                ...$granted,
            ]);
            $user->save();
            $with && $with($user);

            return $user;
        };
        $user = $with ? DB::transaction($open) : $open();

        Auth::login($user);
        session()->regenerate();

        return $user;
    }

    /**
     * @throws ValidationException when it is the only admin of a project
     */
    public function ensureCanGo(User $user, string $errorKey = 'confirmation'): void
    {
        $alone = $user->projects()->wherePivot('is_admin', true)->orderBy('projects.id')->lockForUpdate()->get()
            ->filter(fn ($project) => $project->admins()->count() === 1);

        if ($alone->isNotEmpty()) {
            throw ValidationException::withMessages([$errorKey => __('validation.only_admin_of', [
                'projects' => $alone->pluck('name')->join(', '),
            ]),
            ]);
        }
    }

    /**
     * Deleting, with the account's projects held still: two admins of one project leaving at
     * the same moment cannot both pass.
     *
     * @param  ?callable  $first  What must happen once it may go, before the account is gone
     *
     * @throws ValidationException when it is the only admin of a project
     */
    public function deleteIfItCanGo(User $user, string $errorKey = 'confirmation', ?callable $first = null): void
    {
        DB::transaction(function () use ($user, $errorKey, $first) {
            $this->ensureCanGo($user, $errorKey);
            $first && $first();
            $this->delete($user);
        });
    }

    public function delete(User $user): void
    {
        $avatar = $user->avatar;
        // Their conversations with the Assistant were theirs alone
        Conversation::assistantOf($user)->delete();
        // One by one: a family left with nobody goes with them
        $user->memberships()->get()->each->delete();
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
            // One by one: a family waiting only for one of them may have nobody left
            Invitation::pending()->where('email', $user->email)->get()->each->delete();
            JoinRequest::where('user_id', $user->id)->delete();
            // Support accounts they invited are theirs no more to give
            $this->voidSupportInvitations($user);
            // Nothing reaches their devices any more: unblocked, a device is allowed again
            $user->pushSubscriptions()->delete();
            $this->endSessions($user);
        });
    }

    /** Signed out everywhere, but for the session named (the browser that just proved itself) */
    public function endSessions(User $user, ?string $except = null): void
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->when($except, fn ($sessions) => $sessions->where('id', '!=', $except))
            ->delete();
    }

    /** What someone who no longer runs the platform invited, unanswered yet, is never answered */
    public function voidSupportInvitations(User $inviter): void
    {
        SupportInvitation::where('invited_by', $inviter->id)->whereNull('accepted_at')->delete();
    }

    public function unblock(User $user): void
    {
        $user->forceFill(['blocked_at' => null])->save();
    }
}
