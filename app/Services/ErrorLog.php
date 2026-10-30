<?php

namespace App\Services;

use App\Models\ErrorEntry;
use App\Models\User;
use App\Notifications\NewErrorNotice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Free, self-kept error monitoring: each error the app reports is written once, by where
 * it comes from (its class, file and line), and counted each time it happens again. The
 * deployment's owner is emailed the first time a new one appears. Never fails: whatever
 * goes wrong here is let go.
 */
class ErrorLog
{
    public static function capture(Throwable $error): void
    {
        if (! config('errors.log')) {
            return;
        }

        rescue(function () use ($error) {
            $fingerprint = sha1(get_class($error) . '|' . $error->getFile() . '|' . $error->getLine());
            $now = now();

            $updated = ErrorEntry::where('fingerprint', $fingerprint)->update([
                'count'        => DB::raw('count + 1'),
                'last_seen_at' => $now,
                'message'      => Str::limit($error->getMessage(), 2000),
            ]);
            if ($updated) {
                return;
            }

            $entry = ErrorEntry::create([
                'fingerprint'   => $fingerprint,
                'class'         => get_class($error),
                'message'       => Str::limit($error->getMessage(), 2000),
                'file'          => Str::after($error->getFile(), base_path() . '/'),
                'line'          => $error->getLine(),
                'url'           => app()->runningInConsole() ? null : Str::limit(request()->fullUrl(), 690),
                'user_id'       => Auth::id(),
                'trace'         => Str::limit($error->getTraceAsString(), 12000),
                'first_seen_at' => $now,
                'last_seen_at'  => $now,
            ]);

            User::where('is_owner', true)->first()?->notify(new NewErrorNotice($entry));
        }, report: false);
    }
}
