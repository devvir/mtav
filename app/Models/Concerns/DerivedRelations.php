<?php

namespace App\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait DerivedRelations
{
    /**
     * Allow setting new relations as loaded when another relation already contains all available
     * data. That is: get a new relation loaded for free (no queries) if data is already available.
     *
     * Example Usage
     * -------------
     *
     *  $member->deriveRelation(
     *      derive: 'activeGroups',
     *      from: 'groups',
     *      using: fn ($groups) => $groups->where('active', true)
     *  );
     */
    public function deriveRelation(string $derive, string $from, Closure $using): void
    {
        /** @var Model $model */
        $model = $this;

        $from = Str::camel($from);
        $derive = Str::camel($derive);

        if ($model->relationLoaded($from) && ! $model->relationLoaded($derive)) {
            $model->setRelation($derive, $using($model->getRelation($from)));
        }
    }
}
