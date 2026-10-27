<?php

namespace App\Models;

use App\Enums\EventType;
use App\Enums\LotteryRunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A housing project. Until a superadmin approves it (trusts its managers), it works
 * within the limits in config/limits.php.
 */
class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'organization',
        'active',
        'created_by',
        'approval_requested_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'active'                => 'boolean',
        'approval_requested_at' => 'datetime',
        'approved_at'           => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * Everyone taking part in the project (admins and family members).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot('family_id', 'is_admin')
            ->withTimestamps();
    }

    public function admins(): BelongsToMany
    {
        return $this->members()->wherePivot('is_admin', true);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function unitTypes(): HasMany
    {
        return $this->hasMany(UnitType::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function plan(): HasOne
    {
        return $this->hasOne(Plan::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * The lottery's schedule and description (an event of the project).
     */
    public function lottery(): HasOne
    {
        return $this->hasOne(Event::class)->where('type', EventType::LOTTERY);
    }

    public function lotteryRuns(): HasMany
    {
        return $this->hasMany(LotteryRun::class);
    }

    /**
     * The run that decided (or is deciding) the assignment, if any.
     */
    public function activeLotteryRun(): HasOne
    {
        return $this->hasOne(LotteryRun::class)
            ->whereIn('status', [LotteryRunStatus::RUNNING, LotteryRunStatus::COMPLETED])
            ->latestOfMany();
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function isApproved(): bool
    {
        return (bool) $this->approved_at;
    }

    /**
     * A limit for unapproved projects (null: no limit).
     */
    public function limit(string $key): ?int
    {
        return $this->isApproved() ? null : config("limits.unapproved.{$key}");
    }

    /**
     * Whether preferences are locked: a lottery run is running or decided the assignment.
     */
    public function lotteryLocked(): bool
    {
        return $this->activeLotteryRun()->exists();
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->whereLike('name', "%{$q}%");
    }
}
