<?php

namespace App\Services;

use App\Models\ErrorEntry;
use App\Models\User;
use App\Notifications\NewErrorNotice;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Free, self-kept error monitoring: each error the app reports is written once, by where
 * it comes from (its class and the app's own line that led to it; for a query, the query
 * too), and counted each time it happens again. The deployment's owner is emailed the
 * first time a new one appears. What opens doors or belongs to someone stays out: secrets
 * in the address, a query's values, what a trace passed around. Never fails: whatever goes
 * wrong here is let go.
 */
class ErrorLog
{
    /** A route's parameters that open something to whoever has them */
    private const SECRET_PARAMETERS = ['token', 'key'];

    public static function capture(Throwable $error): void
    {
        if (! config('errors.log')) {
            return;
        }

        rescue(function () use ($error) {
            $fingerprint = sha1($error::class . '|' . self::origin($error) . '|' . ($error instanceof QueryException ? $error->getSql() : ''));
            $message = Str::limit(self::message($error), 2000);
            $now = now();

            $updated = ErrorEntry::where('fingerprint', $fingerprint)->update([
                'count'        => DB::raw('count + 1'),
                'last_seen_at' => $now,
                'message'      => $message,
            ]);
            if ($updated) {
                return;
            }

            $entry = ErrorEntry::create([
                'fingerprint' => $fingerprint,
                'class'       => $error::class,
                'message'     => $message,
                'file'        => Str::after($error->getFile(), base_path() . '/'),
                'line'        => $error->getLine(),
                // A command has no address
                'url'           => app()->runningInConsole() && ! request()->route() ? null : Str::limit(self::address(), 690),
                'user_id'       => Auth::id(),
                'trace'         => Str::limit(self::trace($error), 12000),
                'first_seen_at' => $now,
                'last_seen_at'  => $now,
            ]);

            User::where('is_owner', true)->first()?->notify(new NewErrorNotice($entry));
        }, report: false);
    }

    /** The app's own line that led to the error: the framework throws most from a few of its own */
    private static function origin(Throwable $error): string
    {
        $frames = [['file' => $error->getFile(), 'line' => $error->getLine()], ...$error->getTrace()];

        foreach ($frames as $frame) {
            $file = Str::after($frame['file'] ?? '', base_path() . '/');
            if ($file !== '' && ! Str::startsWith($file, ['/', 'vendor/', 'public/index.php', 'artisan'])) {
                return "{$file}:{$frame['line']}";
            }
        }

        return "{$error->getFile()}:{$error->getLine()}";
    }

    /** What a query that failed says, with its values left out */
    private static function message(Throwable $error): string
    {
        return $error instanceof QueryException
            ? 'SQLSTATE[' . implode('/', array_slice($error->errorInfo ?? [$error->getCode()], 0, 2)) . "]: {$error->getSql()}"
            : $error->getMessage();
    }

    /** Where it happened, with no query string nor what the route takes for a secret */
    private static function address(): string
    {
        $route = request()->route();
        $secrets = collect(self::SECRET_PARAMETERS)
            ->filter(fn ($name) => $route?->hasParameter($name))
            ->map(fn ($name) => (string) $route->parameter($name));

        return str_replace($secrets->all(), '…', request()->url());
    }

    /** The way there, without the values passed along it (a password, an email, a token) */
    private static function trace(Throwable $error): string
    {
        return collect($error->getTrace())
            ->map(fn ($frame, $index) => "#{$index} " . ($frame['file'] ?? '[internal]') . '(' . ($frame['line'] ?? '') . '): '
                . ($frame['class'] ?? '') . ($frame['type'] ?? '') . $frame['function'] . '()')
            ->implode("\n");
    }
}
