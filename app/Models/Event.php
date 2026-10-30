<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\BroadcastsProjectChanges;
use App\Enums\EventType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Something happening in the project: a meeting, an assembly, … or the lottery.
 * Without an end date, an event lasts IMPLICIT_DURATION minutes.
 */
class Event extends Model
{
    use Archivable;
    use RecordsActivity;
    use BroadcastsProjectChanges;

    public const IMPLICIT_DURATION = 60;

    protected $fillable = [
        'project_id',
        'creator_id',
        'type',
        'title',
        'description',
        'location',
        'start_date',
        'end_date',
        'is_published',
        'allows_rsvp',
    ];

    protected $casts = [
        'type'         => EventType::class,
        'start_date'   => 'datetime',
        'end_date'     => 'datetime',
        'is_published' => 'boolean',
        'allows_rsvp'  => 'boolean',
    ];

    /** Drafts, and what nobody answered and only just went out, simply go */
    public function worthKeeping(): bool
    {
        return $this->is_published && ($this->rsvps()->exists() || ! $this->isFresh());
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * People who answered whether they attend.
     */
    public function rsvps(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_rsvps')
            ->withPivot('attending')
            ->withTimestamps();
    }

    public function isLottery(): bool
    {
        return $this->type === EventType::LOTTERY;
    }

    /**
     * upcoming (no date yet, or not started), ongoing, or completed.
     */
    protected function status(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->start_date || $this->start_date->isFuture()) {
                return 'upcoming';
            }

            $end = $this->end_date ?? $this->start_date->copy()->addMinutes(self::IMPLICIT_DURATION);

            return $end->isPast() ? 'completed' : 'ongoing';
        });
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** Not over yet: undated, to come, or under way (an event without an end lasts IMPLICIT_DURATION) */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->whereNull('start_date')
            ->orWhere('end_date', '>', now())
            ->orWhere(fn (Builder $q) => $q->whereNull('end_date')->where('start_date', '>', now()->subMinutes(self::IMPLICIT_DURATION))));
    }

    /** Over: ended, or (without an end) started more than IMPLICIT_DURATION ago */
    public function scopePast(Builder $query): void
    {
        $query->where(fn (Builder $q) => $q->where('end_date', '<=', now())
            ->orWhere(fn (Builder $q) => $q->whereNull('end_date')->where('start_date', '<=', now()->subMinutes(self::IMPLICIT_DURATION))));
    }

    /**
     * The lottery first, then by date (latest first, or soonest first with 'asc'),
     * undated ones last.
     */
    public function scopeSorted(Builder $query, string $direction = 'desc'): void
    {
        $query->orderByRaw("type = 'lottery' DESC")
            ->orderByRaw('start_date IS NULL')
            ->orderBy('start_date', $direction)
            ->orderBy('title');
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('title', "%{$q}%")
            ->orWhereLike('description', "%{$q}%")
            ->orWhereLike('location', "%{$q}%"));
    }
}
