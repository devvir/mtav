<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use Illuminate\Validation\ValidationException;
use App\Enums\EventType;
use App\Enums\LotteryRunStatus;
use App\Enums\ProjectFeature;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A housing project. Until a superadmin approves it (trusts its managers), it works
 * within the limits in config/limits.php. Deleted once it has a history, it is archived
 * instead (soft deleted: gone for everyone but superadmins).
 */
class Project extends Model
{
    use RecordsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'organization',
        'listed',
        'open_to_join',
        'allows_guests',
        'disabled_features',
        'created_by',
        'approval_requested_at',
        'approved_at',
        'approved_by',
    ];

    protected $casts = [
        'listed'                => 'boolean',
        'open_to_join'          => 'boolean',
        'allows_guests'         => 'boolean',
        'disabled_features'     => 'array',
        'approval_requested_at' => 'datetime',
        'approved_at'           => 'datetime',
    ];

    /** Every project has its Community group, whether or not its chat is on */
    protected static function booted(): void
    {
        static::created(function (Project $project) {
            (new Conversation())->forceFill([
                'project_id'   => $project->id,
                'is_group'     => true,
                'is_community' => true,
            ])->save();
        });
    }

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
            || $this->events()->withTrashed()->where('type', '!=', EventType::LOTTERY)->exists()
            || $this->media()->withTrashed()->exists()
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

    public function joinRequests(): HasMany
    {
        return $this->hasMany(JoinRequest::class);
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

    /** Its forum's topics */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    /** Every post of its forum (a post's address resolves through its project) */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /** Every message of its chat (addresses of a message are scoped by it) */
    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(Message::class, Conversation::class);
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

    /**
     * Its admins want it listed, and the platform trusts them: a project nobody has
     * vetted yet is not shown to everyone.
     */
    /**
     * Whether someone new has a way to take part. With guests they always do; without, they
     * need a family, which takes unit types to choose from and a lottery yet to decide.
     */
    public function acceptsNewcomers(): bool
    {
        return $this->allows_guests || ($this->unitTypes()->exists() && ! $this->lotteryLocked());
    }

    public function hasFeature(ProjectFeature|string $feature): bool
    {
        $name = $feature instanceof ProjectFeature ? $feature->value : $feature;

        return ! in_array($name, $this->disabled_features ?? [], true);
    }

    /** @return array<string, bool>  each feature, and whether it is on */
    public function features(): array
    {
        return collect(ProjectFeature::cases())
            ->mapWithKeys(fn (ProjectFeature $feature) => [$feature->value => $this->hasFeature($feature)])
            ->all();
    }

    public function isInDirectory(): bool
    {
        return $this->listed && $this->isApproved();
    }

    public function scopeInDirectory(Builder $query): void
    {
        $query->where('listed', true)->whereNotNull('approved_at');
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

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->whereLike('name', "%{$q}%");
    }
}
