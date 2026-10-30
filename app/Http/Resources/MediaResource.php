<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Versioned by the stored file: its address never serves, from a cache, another file
        // that once had its number (data started over)
        $params = ['project' => $this->project_id, 'media' => $this->id, 'v' => substr(md5($this->path), 0, 10)];

        return [
            'id'          => $this->id,
            'category'    => $this->category->value,
            'description' => $this->description,
            'alt_text'    => $this->alt_text,
            'mime_type'   => $this->mime_type,
            'file_size'   => $this->file_size,
            'width'       => $this->width,
            'height'      => $this->height,
            'url'         => route('media.file', $params),
            'thumbnail'   => $this->thumbnail ? route('media.thumbnail', $params) : null,
            'owner'       => $this->whenLoaded('owner', fn () => $this->owner ? ['id' => $this->owner->id, 'name' => $this->owner->name] : null),
            'created_at'  => $this->created_at,
            'can'         => [
                'update' => $request->user()?->can('update', $this->resource),
                'delete' => $request->user()?->can('delete', $this->resource),
            ],
        ];
    }
}
