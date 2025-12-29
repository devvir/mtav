<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * @property-read Media $resource
 *
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'path'           => $this->path,
            'description'    => $this->description,
            'width'          => $this->width,
            'height'         => $this->height,
            'category'       => $this->category->value,
            'category_label' => $this->category->label(),
            'mime_type'      => $this->mime_type,
            'file_size'      => $this->file_size,
            'is_image'       => $this->isImage(),

            ...$this->relationsData(),
            ...$this->computedAttributes(),
        ];
    }

    protected function relationsData(): array
    {
        return [
            'owner'   => $this->whenLoaded('owner', default: ['id' => $this->owner_id]),
            'project' => $this->whenLoaded('project', default: ['id' => $this->project_id]),
        ];
    }

    /**
     * Derived or computed attributes.
     */
    protected function computedAttributes()
    {
        $filetype = $this->getFileTypeName();

        return [
            'filetype'            => $filetype,
            'url'                 => $this->path2url($this->path),
            'thumbnail'           => $this->thumbnail ? $this->path2url($this->thumbnail) : null,
            'extension'           => pathinfo($this->path, PATHINFO_EXTENSION),
            'alt_text'            => $this->alt_text ?? $filetype,
            'file_size_formatted' => Number::fileSize($this->file_size),
        ];
    }

    /**
     * Get the URL for this media file.
     */
    protected function path2url(string $path): string
    {
        return str_starts_with($path, '/') ? $path : Storage::url($path);
    }

    /**
     * Get a human-readable file type name based on MIME type.
     */
    protected function getFileTypeName(): string
    {
        $key = 'files.' . $this->mime_type;

        return (__($key) !== $key) ? __($key) : __('files.document');
    }
}
