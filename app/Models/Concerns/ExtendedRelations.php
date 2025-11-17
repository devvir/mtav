<?php

namespace App\Models\Concerns;

use App\Relations\BelongsToThrough;

trait ExtendedRelations
{
    /**
     * A model belongs to another through a third one.
     *
     * Example: Log belongsTo Post and Post belongsTo User
     *          > Log@user(): $this->belongsToThrough(User, Post)
     */
    public function belongsToThrough(
        string $related,
        string $through,
        ?string $firstKey = null,
        ?string $secondKey = null,
        ?string $localKey = null,
        ?string $secondLocalKey = null
    ) {
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
}
