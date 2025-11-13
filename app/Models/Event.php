<?php

namespace App\Models;

use App\Enums\EventType;
use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    use ProjectScope;

    protected $casts = [
        'type'         => EventType::class,
        'start_date'   => 'datetime',
        'end_date'     => 'datetime',
        'is_published' => 'boolean',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        $query->where('start_date', '>', now());
    }

    public function scopePast(Builder $query): void
    {
        $query->where('end_date', '<', now());
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
