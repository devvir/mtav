<?php

namespace App\Http\Resources\Concerns;

use App\Enums\MediaCategory;

trait HasMedia
{
    public function sharedMediaData(): array
    {
        /** Auto-set images relation, if media relation is loaded */
        $this->resource->deriveRelation(
            from: 'media',
            derive: 'images',
            using: fn ($media) => $media->where('category', MediaCategory::IMAGE),
        );

        /** Auto-set videos relation, if media relation is loaded */
        $this->resource->deriveRelation(
            from: 'media',
            derive: 'videos',
            using: fn ($media) => $media->where('category', MediaCategory::VIDEO),
        );

        return [
            'media'        => $this->whenLoaded('media'),
            'media_count'  => $this->whenCountedOrLoaded('media'),
            'images'       => $this->whenLoaded('images'),
            'images_count' => $this->whenCountedOrLoaded('images'),
            'videos'       => $this->whenLoaded('videos'),
            'videos_count' => $this->whenCountedOrLoaded('videos'),
        ];
    }
}
