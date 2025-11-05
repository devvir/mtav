<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource as BaseJsonResource;
use Illuminate\Http\Resources\MissingValue;

abstract class JsonResource extends BaseJsonResource
{
    protected function whenCountedOrLoaded(string $relation): MissingValue|int
    {
        return $this->whenCounted(
            $relation,
            default: fn () => $this->whenLoaded($relation, fn () => $this->$relation->count())
        );
    }
}
