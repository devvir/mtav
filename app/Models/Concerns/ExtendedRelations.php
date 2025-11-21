<?php

namespace App\Models\Concerns;

use App\Relations\BelongsToOneOrMany;
use App\Relations\BelongsToThrough;
use Illuminate\Database\Eloquent\Model;

trait ExtendedRelations
{
    /**
     * A model belongs to another through a third one.
     *
     * Example: Log belongsTo Post and Post belongsTo User
     *          > Log@user(): $this->belongsToThrough(User, Post)
     *
     * @param class-string<Model> $related
     * @param class-string<Model> $through
     */
    public function belongsToThrough(
        string $related,
        string $through,
        ?string $firstKey = null,
        ?string $secondKey = null,
        ?string $localKey = null,
        ?string $secondLocalKey = null
    ): BelongsToThrough {
        $related = $this->newRelatedInstance($related);
        $through = $this->newRelatedThroughInstance($through);

        return new BelongsToThrough(
            $related->newQuery(),
            $this,
            $through,
            $firstKey ?: $through->getKeyName(),
            $secondKey ?: $related->getKeyName(),
            $localKey ?: $through->getForeignKey(),
            $secondLocalKey ?: $related->getForeignKey(),
        );
    }

    /**
     * Extension of BelongsToMany allowing @one() as in HasMany relationships.
     *
     * @param class-string<Model> $related
     */
    public function belongsToOneOrMany(
        string $related,
        ?string $table = null,
        ?string $foreignPivotKey = null,
        ?string $relatedPivotKey = null,
        ?string $parentKey = null,
        ?string $relatedKey = null,
        ?string $relation = null,
    ): BelongsToOneOrMany {
        $instance = $this->newRelatedInstance($related);

        return new BelongsToOneOrMany(
            $instance->newQuery(),
            $this,
            $table ?: $this->joiningTable($related, $instance),
            $foreignPivotKey ?: $this->getForeignKey(),
            $relatedPivotKey ?: $instance->getForeignKey(),
            $parentKey ?: $this->getKeyName(),
            $relatedKey ?: $instance->getKeyName(),
            $relation ?: $this->guessBelongsToManyRelation(),
        );
    }
}
