<?php

namespace App\Models\Concerns;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait ProjectScope
{
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

        if (self::noScopeRequired()) {
            return;
        }

        /** @var Collection $filter */
        $filter = Auth::user()->projects->pluck('id');

        $scope = method_exists(static::class, 'projects')
            ? fn ($builder) => self::belongsToManyScope($builder, $filter)
            : fn ($builder) => self::belongsToScope($builder, $filter);

        static::addGlobalScope('projectScope', $scope);
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
        $belongsToProject = method_exists(static::class, 'project');
        $belongsToProjects = method_exists(static::class, 'projects');

        if (! $belongsToProject && ! $belongsToProjects) {
            throw new Exception('Model ' . static::class . ' does not belong to Projects');
        }
    }

    /**
     * Project Scope only applies to non-superadmin authenticated users.
     */
    protected static function noScopeRequired(): bool
    {
        return Auth::guest() || Auth::user()->isSuperadmin();
    }
}