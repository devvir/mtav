<?php

namespace App\Models;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Services\Notifications\NotificationCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

class Notification extends Model
{
    protected $casts = [
        'data'      => 'array',
        'type'      => NotificationType::class,
        'target'    => NotificationTarget::class,
        'target_id' => 'int',
    ];

    /**
     * Use a custom Eloquent Collection.
     */
    public function newCollection(array $models = []): NotificationCollection
    {
        return new NotificationCollection($models);
    }

    /**
     * Search notifications by title or message within the JSON data field.
     */
    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(
            fn (Builder $q) => $q
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.title')) LIKE ?", ["%{$search}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.message')) LIKE ?", ["%{$search}%"])
        );
    }

    /**
     * Users who have read this notification.
     */
    public function readBy(): BelongsToMany
    {
        /** TODO : shouldn't we use the Pivot NotificationRead? */
        return $this->belongsToMany(User::class, 'notification_read')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Check if a specific user has read this notification.
     */
    public function isReadBy(User $user): bool
    {
        return $this->readBy()->whereUserId($user->id)->exists();
    }

    /**
     * Mark this notification as read by a user.
     */
    public function markAsReadBy(User $user): void
    {
        if (! $this->isReadBy($user)) {
            $this->readBy()->attach($user->id, ['read_at' => now()]);
        }
    }

    /**
     * Mark this notification as unread by a user.
     */
    public function markAsUnreadBy(User $user): void
    {
        $this->readBy()->detach($user->id);
    }

    /**
     * Scope query to notifications read by the given user.
     */
    public function scopeReadBy(Builder $query, User $user): void
    {
        $query->whereHas('readBy', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    /**
     * Scope query to notifications NOT read by the given user.
     */
    public function scopeUnreadBy(Builder $query, User $user): void
    {
        $query->whereDoesntHave('readBy', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    /**
     * Scope query to global notifications.
     */
    public function scopeGlobal(Builder $query): void
    {
        $query->whereTarget(NotificationTarget::GLOBAL);
    }

    /**
     * Scope query to project notifications.
     */
    public function scopeProject(Builder $query, Project|int|null $projectOrId = null): void
    {
        $this->scopeProjects($query, $projectOrId ? [$projectOrId] : null);
    }

    /**
     * Scope query to project notifications in the given projects.
     */
    public function scopeProjects(Builder $query, Project|iterable|null $projectOrIds = null): void
    {
        $projectIds = collect($projectOrIds)->map(fn ($projectOrId) => $projectOrId->id ?? $projectOrId);

        $query->whereTarget(NotificationTarget::PROJECT)
            ->when($projectIds->isNotEmpty(), fn (Builder $q) => $q->whereIn('target_id', $projectIds));
    }

    /**
     * Scope query to private (user) notifications.
     */
    public function scopePrivate(Builder $query, User|int|null $userOrId = null): void
    {
        $query->whereTarget(NotificationTarget::PRIVATE)
            ->when($userOrId, fn (Builder $q) => $q->where('target_id', $userOrId->id ?? $userOrId));
    }

    /**
     * Scope query to the notifications for a given user (private, project and global if applicable).
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $projectIds = $user->isSuperadmin() ? null : $user->projects()->pluck('projects.id');
        $globalAccess = $user->isSuperadmin() || ($projectIds->count() > 1);

        $query->where(
            fn (Builder $q) => $q
                ->orWhere(fn (Builder $q1) => $this->scopePrivate($q1, $user))
                ->orWhere(fn (Builder $q2) => $this->scopeProjects($q2, $projectIds))
                ->orWhere(fn (Builder $q3) => $q3->when($globalAccess, fn (Builder $q4) => $this->scopeGlobal($q4)))
        );
    }

    /**
     * Set default sorting and filtering.
     */
    protected static function booted(): void
    {
        /**
         * Load Notifications in reverse order by default.
         */
        static::addGlobalScope(
            'recentFirst',
            fn (Builder $query) => $query->orderBy('id', 'desc')
        );

        /**
         * Only include Notifications created after the User was invited to MTAV.
         */
        if (Auth::check()) {
            static::addGlobalScope(
                'sinceRegistration',
                fn (Builder $query) => $query->where('created_at', '>', Auth::user()->created_at)
            );
        }
    }
}
