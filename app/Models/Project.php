<?php

namespace App\Models;

use Illuminate\Validation\ValidationException;
use App\Enums\EventType;
use App\Enums\LotteryRunStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A housing project. Until a superadmin approves it (trusts its managers), it works
 * within the limits in config/limits.php. Deleted once it has a history, it is archived
 * instead (soft deleted: gone for everyone but superadmins).
 */
class Project extends Model
{
    use SoftDeletes;

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

    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Whether it has a history worth keeping for audits: other people took part (or were
     * invited), it held events (besides its lottery's own), shared files or ran its
     * lottery. A project its creator only set up has none, and is deleted for good.
     */
    public function hasHistory(): bool
    {
        return $this->memberships()->where('user_id', '!=', $this->created_by)->exists()
            || $this->invitations()->exists()
            || $this->events()->where('type', '!=', EventType::LOTTERY)->exists()
            || $this->media()->exists()
            || $this->lotteryRuns()->exists();
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
    /**
     * A run is being solved: who takes part, and what is given out, holds still until it
     * ends (what it decides from is what its audit recorded).
     *
     * @throws ValidationException
     */
    public function ensureLotteryNotRunning(): void
    {
        if ($this->lotteryRuns()->where('status', LotteryRunStatus::RUNNING)->exists()) {
            throw ValidationException::withMessages(['lottery' => __('lottery.running')]);
        }
    }

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
