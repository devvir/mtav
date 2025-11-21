<?php

namespace App\Http\Resources\Concerns;

use App\Enums\MediaCategory;

trait HasMedia
{
    public function sharedMediaData(): array
    {
        $this->deriveSubMediaRelations(relation: 'audios', category: MediaCategory::AUDIO);
        $this->deriveSubMediaRelations(relation: 'documents', category: MediaCategory::DOCUMENT);
        $this->deriveSubMediaRelations(relation: 'images', category: MediaCategory::IMAGE);
        $this->deriveSubMediaRelations(relation: 'videos', category: MediaCategory::VIDEO);

        return [
            'media'           => $this->whenLoaded('media'),
            'media_count'     => $this->whenCountedOrLoaded('media'),
            'audios'          => $this->whenLoaded('audios'),
            'audios_count'    => $this->whenCountedOrLoaded('audios'),
            'documents'       => $this->whenLoaded('documents'),
            'documents_count' => $this->whenCountedOrLoaded('documents'),
            'images'          => $this->whenLoaded('images'),
            'images_count'    => $this->whenCountedOrLoaded('images'),
            'videos'          => $this->whenLoaded('videos'),
            'videos_count'    => $this->whenCountedOrLoaded('videos'),

            'visual_media'       => $this->whenLoaded('visualMedia'),
            'visual_media_count' => $this->whenCountedOrLoaded('visualMedia'),
        ];
    }

    public function deriveSubMediaRelations(string $relation, MediaCategory $category): void
    {
        /** Auto-set images relation, if media relation is loaded */
        $this->resource->deriveRelation(
            from: 'media',
            derive: $relation,
            using: fn ($media) => $media->where('category', $category),
        );
    }
}
