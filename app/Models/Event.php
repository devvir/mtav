<?php

namespace App\Models;

use App\Enums\EventType;
use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Event extends Model
{
    use ProjectScope;

    protected $casts = [
        'type'         => EventType::class,
        'start_date'   => 'datetime',
        'end_date'     => 'datetime',
        'is_published' => 'boolean',
        'rsvp'         => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    public function rsvps(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'event_rsvp', relatedPivotKey: 'user_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function allowsRsvp(): bool
    {
        return $this->rsvp;
    }

    public function isLottery(): bool
    {
        return $this->type === EventType::LOTTERY;
    }

    public function isOnline(): bool
    {
        return $this->type === EventType::ONLINE;
    }

    public function isOnSite(): bool
    {
        return $this->type === EventType::ONSITE;
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->where('end_date', '>', now());
    }

    public function scopePast(Builder $query): void
    {
        $query->where('end_date', '<', now());
    }

    public function scopeAcknowledgedBy(Builder $query, Member|int $member, bool $status = true): void
    {
        $memberId = $member instanceof Member ? $member->id : $member;

        $query->whereHas(
            'rsvps',
            fn (Builder $q) => $q
            ->where('user_id', $memberId)
            ->wherePivot('status', $status),
        );
    }

    public function scopeAcceptedBy(Builder $query, Member|int $member): void
    {
        $this->scopeAcknowledgedBy($query, $member, true);
    }

    public function scopeRejectedBy(Builder $query, Member|int $member): void
    {
        $this->scopeAcknowledgedBy($query, $member, false);
    }

    public function scopeSorted(Builder $query): void
    {
        $query->orderByRaw("IF(type = 'lottery', 0, 1), start_date, title");
    }

    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(
            fn ($q) => $q
                ->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
        );
    }
}
