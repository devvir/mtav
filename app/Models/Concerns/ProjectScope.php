<?php

namespace App\Models\Concerns;

use App\Models\Project;
use App\Models\User;
use Exception;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait ProjectScope
{
    /**
     * Filter query on ProjectScoped Model by a given parent Project.
     */
    public function scopeInProject(Builder $query, int|Project $project): void
    {
        $projectId = is_int($project) ? $project : $project->getKey();

        if (method_exists($this, 'projects') && $this->projects() instanceof BelongsToMany) {
            $query->whereHas('projects', fn ($q) => $q->where('projects.id', $projectId));
        } elseif (method_exists($this, 'project')) {
            $foreignKey = $this->getTable() . '.' . $this->project()->getForeignKeyName();

            $query->where($foreignKey, $projectId);
        } else {
            throw new Exception('Class ' . class_basename($this) . ' cannot implement inProject scope');
        }
    }

    /**
     * For authenticated users, apply a Project scope to Models using this trait.
     *
     * NOTE: Parent Model MUST define a `projects()` or `project()` relationship.
     *
     * This trait will limit all parent queries to Projects from that relationship.
     */
    public static function bootProjectScope(): void
    {
        self::ensureModelBelongsToProjects();

        $scopeFn = method_exists(static::class, 'projects')
            ? self::belongsToManyScope(...)
            : self::belongsToScope(...);

        static::addGlobalScope('projectScope', function (Builder $builder) use ($scopeFn) {
            if (static::noScopingRequired()) {
                return;
            }

            $projects = Auth::user()->projects->pluck('id');
            $scopeFn($builder, $projects);
        });
    }

    protected static function belongsToManyScope(Builder $builder, Collection $filter)
    {
        $builder->whereHas(
            'projects',
            fn ($q) => $q->whereIn('projects.id', $filter)
        );
    }

    protected static function belongsToScope(Builder $builder, Collection $filter): void
    {
        $model = $builder->getModel();

        $builder->whereIn(
            $model->getTable() . '.' . $model->project()->getForeignKeyName(),
            $filter
        );
    }

    /**
     * @throws Exception if parent Model does not have a `project` relation (belongsTo),
     *                   or a `projects` relation (belongsToMany).
     */
    protected static function ensureModelBelongsToProjects(): void
    {
        $belongsToProjects = method_exists(static::class, 'projects');
        $belongsToProject = method_exists(static::class, 'project');

        if (! $belongsToProject && ! $belongsToProjects) {
            throw new Exception('Model ' . static::class . ' does not belong to Projects');
        }
    }

    /**
     * Project Scope only applies to non-superadmin authenticated users.
     */
    protected static function noScopingRequired(): bool
    {
        // Skip Scoping for Laravel's fetching of the Authenticated User
        $fetchingAuthUser = ! Auth::hasUser()
            && static::class === User::class
            && collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, limit: 15))
                ->pluck('class')
                ->contains(EloquentUserProvider::class);

        return $fetchingAuthUser || Auth::guest() || Auth::user()->isSuperadmin();
    }
}