<?php

namespace App\Models;

use App\Relations\BelongsToOneOrMany;
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
     * Get the project that the member is currently member of (one or none).
     */
    public function project(): BelongsToOneOrMany
    {
        return $this->projects()->one();
    }

    /**
     * A member may belong to exactly one family or none.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get all events for the Member's Project.
     */
    public function events(): HasMany
    {
        /** @var Project $project */
        $project = $this->projects()->latest()->first();

        return $project->events()->published();
    }

    /**
     * Get upcoming published events for the member's Project.
     */
    public function upcomingEvents(): HasMany
    {
        return $this->events()->upcoming();
    }

    /**
     * Get the list of Events accepted or declined by the member.
     */
    public function acknowledgedEvents(): BelongsToMany
    {
        return $this->rsvps()->wherePivotNotNull('status');
    }

    /**
     * Get the list of Events accepted by the member (will go).
     */
    public function acceptedEvents(): BelongsToMany
    {
        return $this->rsvps()->wherePivot('status', true);
    }

    /**
     * Get the list of Events declined by the member (won't go).
     */
    public function declinedEvents(): BelongsToMany
    {
        return $this->rsvps()->wherePivot('status', false);
    }

    /**
     * Get all events the member has been invited to (acknowledged or not).
     */
    public function rsvps(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_rsvp', 'user_id')
            ->withPivot('status')
            ->withTimestamps();
    }

    /**
     * Get upcoming events the member has been invited to (acknowledged or not).
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
            $query->where('is_admin', false)->whereNotNull('family_id');
        });
    }
}
