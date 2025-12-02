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

    protected function commonResourceData(): array
    {
        return [
            'id'          => $this->id,
            'is_deleted'  => $this->whenHas('deleted_at', fn () => !! $this->deleted_at),
            'created_at'  => $this->created_at,
            'created_ago' => $this->created_at->diffForHumans(),
            'deleted_at'  => $this->whenHas('deleted_at', fn () => $this->deleted_at),
        ];
    }

    protected function whenCountedOrLoaded(string $relation): MissingValue|int
    {
        return $this->whenCounted(
            $relation,
            default: fn () => $this->whenLoaded($relation, fn () => $this->$relation->count())
        );
    }
}
