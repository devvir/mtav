<?php

namespace App\Http\Resources;

use Devvir\ResourceTools\Concerns\ResourceSubsets;
use Devvir\ResourceTools\Concerns\WithResourceAbilities;
use Illuminate\Http\Resources\Json\JsonResource as BaseJsonResource;
use Illuminate\Http\Resources\MissingValue;

abstract class JsonResource extends BaseJsonResource
{
    use ResourceSubsets;
    use WithResourceAbilities;

    protected function whenCountedOrLoaded(string $relation): MissingValue|int
    {
        return $this->whenCounted(
            $relation,
            default: fn () => $this->whenLoaded($relation, fn () => $this->$relation->count())
        );
    }
}
