<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends User
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'users';

    /**
     * A member may belong to exactly one family or none.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the project that the member is currently an active member of (one or none).
     */
    public function project(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->projects->last(),
        );
    }

    /**
     * Get all events for the member's project.
     */
    public function events(): HasMany
    {
        /** @var Project $project */
        $project = $this->projects()->latest()->first();

        return $project->events()->published();
    }

    /**
     * Get upcoming published events for the member's project.
     */
    public function upcomingEvents(): HasMany
    {
        return $this->events()->upcoming();
    }

    /**
     * Get upcoming published events for the member's project.
     */
    public function acknowledgedEvents(): BelongsToMany
    {
        return $this->rsvps()->wherePivotNotNull('status');
    }

    /**
     * Get upcoming published events for the member's project.
     */
    public function acceptedEvents(): BelongsToMany
    {
        return $this->rsvps()->wherePivot('status', true);
    }

    /**
     * Get upcoming published events for the member's project.
     */
    public function rejectedEvents(): BelongsToMany
    {
        return $this->rsvps()->wherePivot('status', false);
    }

    /**
     * Get all events the member has RSVP'd to.
     */
    public function rsvps(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_rsvp', 'user_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Get upcoming events the member has RSVP'd to.
     */
    public function upcomingRsvps(): BelongsToMany
    {
        return $this->rsvps()->upcoming();
    }

    public function joinProject(Project|int $project): self
    {
        $this->projects->each->removeMember($this);

        model($project, Project::class)->addMember($this);

        return $this;
    }

    public function leaveProject(Project $project): self
    {
        $project->removeMember($this);

        return $this;
    }

    public function switchProject(Project $project): self
    {
        $this->project?->removeMember($this);

        $project->addMember($this);

        return $this;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('member', function ($query) {
            $query->where('is_admin', false);
        });
    }
}
