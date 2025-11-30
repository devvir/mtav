<?php

namespace App\Models;

use App\Enums\EventType;
use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use ProjectScope;
    use SoftDeletes;

    /**
     * Default Event duration (in minutes) if no end date is set.
     */
    public const IMPLICIT_DURATION = 60;

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

    public function status(): Attribute
    {
        $upcoming = is_null($this->start_date) || $this->start_date > now();
        $completed = is_null($this->end_date) && isset($this->start_date)
            ? $this->start_date < now()->subMinutes(self::IMPLICIT_DURATION)
            : isset($this->end_date) && $this->end_date < now();

        return Attribute::make(get: fn () => match (true) {
            $completed => 'completed',
            $upcoming  => 'upcoming',
            default    => 'ongoing',
        });
    }

    public function scopeOnsite(Builder $query): void
    {
        $query->whereType(EventType::ONSITE);
    }

    public function scopeOnline(Builder $query): void
    {
        $query->whereType(EventType::ONLINE);
    }

    public function scopeLottery(Builder $query): void
    {
        $query->whereType(EventType::LOTTERY);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Upcoming Events
     *   1. No start date set (i.e. TBD)
     *   2. or has a start date in the future
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where(
            fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '>', now())
        );
    }

    /**
     * Ongoing Event
     *   1. started in the past
     *   2. hasn't finished (ends in future or started recently with no end date given)
     */
    public function scopeOngoing(Builder $query): void
    {
        $query
            ->whereNot(fn ($q) => $this->scopeUpcoming($q))
            ->whereNot(fn ($q) => $this->scopePast($q));
    }

    /**
     * Past Event
     *   1. ended in the past (explicit)
     *   2. or started too long ago with no end date set
     */
    public function scopePast(Builder $query): void
    {
        $timecut = now()->subMinutes(self::IMPLICIT_DURATION);

        $query->where(  // Explicit end date in the past
            fn ($q) => $q->whereNotNull('end_date')->where('end_date', '<', now())
        )->orWhere(     // or implicitely ended (started too long ago)
            fn ($q) => $q->whereNull('end_date')->where('start_date', '<', $timecut)
        );
    }

    /**
     * Acknowledged by a Member (i.e. RSVP accepted or declined).
     */
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

    public function scopeDeclinedBy(Builder $query, Member|int $member): void
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
