<?php

namespace App\Relations;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * TODO : make the abstraction better by returning:
 * - a real BelongsToMany if @one() is not called
 * - a BelongsToOneOfMany if @one() is called
 */
class BelongsToOneOrMany extends BelongsToMany
{
    protected bool $one = false;

    public function one(): static
    {
        $this->one = true;

        return $this->limit(1);
    }

    /**
     * Last step for standalone Model or if automaticallyEagerLoadRelationships = false.
     */
    public function getResults()
    {
        return $this->one ? parent::getResults()->first() : parent::getResults();
    }

    /**
     * Last step for Model within an Eloquent\Collection (w/ automaticallyEagerLoadRelationships).
     */
    public function match(array $models, Collection $results, $relation)
    {
        $models = parent::match($models, $results, $relation);

        $this->one && collect($models)->each(
            fn ($model) => $model->setRelation($relation, $model->getRelation($relation)->first())
        );

        return $models;
    }
}
