<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Writes what happened, for superadmins to review: who did it (by name at the time, kept
 * after their account is gone), to what, on which project, from where. Never what people
 * read or look at: only changes and the ways in. Recording never fails what it records.
 */
class ActivityLog
{
    /** Never written down, whatever changes */
    private const SECRET = ['password', 'remember_token', 'token', 'token_hash', 'google_id', 'updated_at', 'created_at'];

    /** A person's fields whose values are the platform's business (their roles) */
    private const ROLES = ['is_superadmin', 'is_owner'];

    public static function record(string $action, ?Model $subject = null, array $changes = [], ?User $actor = null): void
    {
        rescue(function () use ($action, $subject, $changes, $actor) {
            $actor ??= Auth::user();

            Activity::create([
                'user_id'      => $actor?->getKey(),
                'actor'        => $actor?->name,
                'action'       => $action,
                'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
                'subject_id'   => is_int($subject?->getKey()) ? $subject->getKey() : null,
                'subject'      => $subject ? self::label($subject) : null,
                'project_id'   => $subject instanceof Project ? $subject->getKey() : ($subject?->getAttributes()['project_id'] ?? null),
                'changes'      => $changes ?: null,
                'ip'           => app()->runningInConsole() ? null : request()->ip(),
                'created_at'   => now(),
            ]);
        }, report: false);
    }

    /**
     * What changed in a model just saved: each field with its value before and after.
     * A person's own data is noted by field only: that it changed, not to what.
     *
     * @return array<string, array{0: mixed, 1: mixed}|null>
     */
    public static function changes(Model $model): array
    {
        $personal = $model instanceof User;

        return collect($model->getChanges())
            ->except(self::SECRET)
            ->mapWithKeys(fn ($value, $field) => [
                $field => $personal && ! in_array($field, self::ROLES, true) ? null : [$model->getOriginal($field), $value],
            ])
            ->all();
    }

    /** What the subject is called now: its name, title or identifier */
    private static function label(Model $subject): ?string
    {
        // Read from what the model holds: asking a model for what it lacks is refused
        $attributes = $subject->getAttributes();
        if ($subject instanceof User) {
            return Str::limit($subject->name, 250);
        }
        foreach (['name', 'title', 'identifier', 'email', 'key', 'uuid'] as $attribute) {
            if (filled($value = $attributes[$attribute] ?? null)) {
                return Str::limit((string) $value, 250);
            }
        }

        return null;
    }
}
