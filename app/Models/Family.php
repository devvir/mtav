<?php

namespace App\Models;

use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Family extends Model
{
    use ProjectScope;
    use SoftDeletes;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function addMember(Member|int $memberOrId): self
    {
        $member = model($memberOrId, Member::class);

        $this->members()->save($member);

        return $this;
    }

    public function join(Project|int $projectOrId): self
    {
        $project = model($projectOrId, Project::class);

        $this->members->each($project->addMember(...));

        $this->project()->associate($project)->save();

        return $this;
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }

    public function scopeInProject(Builder $query, int|Project $project): void
    {
        $projectId = is_int($project) ? $project : $project->getKey();

        $query->where('project_id', $projectId);
    }

    public function scopeWithMembers(Builder $query): void
    {
        $query->whereHas('members');
    }

    public function scopeSearch(Builder $query, string $q, bool $searchMembers = false): void
    {
        $query
            ->whereLike('name', "%$q%")
            ->when($searchMembers, fn (Builder $query) => $query->orWhereHas(
                'members',
                fn (Builder $query) => $query->whereRaw('CONCAT(firstname, " ", lastname) LIKE ?', "%$q%")
            ));
    }
}
