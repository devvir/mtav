<?php

namespace App\Services\Plan;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * One person draws a plan at a time: the editor holds it while open (renewing it as it goes)
 * and lets go when done; one left open in a closed window lets go by itself a little later.
 * Anyone else is told who is drawing, rather than saving over them.
 */
class EditingLock
{
    /** How long a hold lasts unless renewed (the editor renews it every 30 seconds) */
    public const SECONDS = 90;

    /** Takes the plan, or keeps it, for the user; who holds it instead, if anyone else does */
    public function take(Plan $plan, User $user): ?User
    {
        $key = $this->key($plan);
        if (Cache::add($key, $user->id, self::SECONDS)) {
            return null;
        }

        $holder = Cache::get($key);
        if ($holder === null || $holder === $user->id) {
            Cache::put($key, $user->id, self::SECONDS);

            return null;
        }

        return User::find($holder);
    }

    /** Whoever else is drawing the plan now, if anyone */
    public function heldByAnother(Plan $plan, User $user): ?User
    {
        $holder = Cache::get($this->key($plan));

        return $holder !== null && $holder !== $user->id ? User::find($holder) : null;
    }

    public function release(Plan $plan, User $user): void
    {
        if (Cache::get($this->key($plan)) === $user->id) {
            Cache::forget($this->key($plan));
        }
    }

    private function key(Plan $plan): string
    {
        return "plan-editor:{$plan->id}";
    }
}
