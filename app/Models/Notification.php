<?php

namespace App\Models;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * An in-app notification, addressed to one user (private), to everyone in a project,
 * or to the platform's superadmins (global).
 */
class Notification extends Model
{
    protected $fillable = [
        'type',
        'target',
        'target_id',
        'data',
        'triggered_by',
    ];

    protected $casts = [
        'data'      => 'array',
        'type'      => NotificationType::class,
        'target'    => NotificationTarget::class,
        'target_id' => 'integer',
    ];

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'notification_reads')->withPivot('read_at');
    }

    public function isFor(User $user): bool
    {
        return match ($this->target) {
            NotificationTarget::PRIVATE => $this->target_id === $user->id,
            NotificationTarget::PROJECT => $user->canAccess($this->target_id),
            NotificationTarget::GLOBAL  => $user->isSuperadmin(),
        };
    }

    public function markAsReadBy(User $user): void
    {
        $this->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);
    }

    public function markAsUnreadBy(User $user): void
    {
        $this->readers()->detach($user->id);
    }

    /**
     * Notifications a user receives, newest first, from their registration on, each
     * with a `read` flag for that user. An archived project's are no longer among them.
     */
    public function scopeFor(Builder $query, User $user): void
    {
        $projectIds = Project::select('id')->whereIn('id', $user->memberships->pluck('project_id'));

        $query
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $q) => $q
                    ->where('target', NotificationTarget::PRIVATE)
                    ->where('target_id', $user->id))
                ->orWhere(fn (Builder $q) => $q
                    ->where('target', NotificationTarget::PROJECT)
                    ->whereIn('target_id', $projectIds))
                ->when($user->isSuperadmin(), fn (Builder $q) => $q
                    ->orWhere('target', NotificationTarget::GLOBAL)))
            ->where('created_at', '>=', $user->created_at)
            ->withExists(['readers as read' => fn (Builder $q) => $q->whereKey($user->id)])
            ->orderByDesc('id');
    }

    public function scopeUnreadBy(Builder $query, User $user): void
    {
        $query->whereDoesntHave('readers', fn (Builder $q) => $q->whereKey($user->id));
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('data->title', "%{$q}%")
            ->orWhereLike('data->message', "%{$q}%"));
    }
}
