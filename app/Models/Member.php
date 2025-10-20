<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Member extends User
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'users';

    /**
     * A user may belong to exactly one family or none.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * Get the project that the user is currently an active member of (one or none).
     */
    public function getProjectAttribute(): ?Project
    {
        return $this->projects->where('pivot.active', true)->first();
    }

    public function joinProject(Project|int $project): self
    {
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
