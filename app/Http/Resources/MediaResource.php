<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read Media $resource
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'path'           => $this->path,
            'url'            => $this->path2url($this->path),
            'thumbnail'      => $this->path2url($this->thumbnail),
            'description'    => $this->description,
            'alt_text'       => $this->alt_text,
            'width'          => $this->width,
            'height'         => $this->height,
            'category'       => $this->category->value,
            'category_label' => $this->category->label(),
            'mime_type'      => $this->mime_type,
            'file_size'      => $this->file_size,
            'is_image'       => $this->isImage(),

            ...$this->relationsData(),
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
     * Get the URL for this media file, using mock images for dev files.
     */
    protected function path2url(string $path): string
    {
        // Seeded Dev files ('dev-' prefix) get a mock image/thumbnail
        if (str_starts_with(basename($path), 'dev-')) {
            return $this->getMockImageUrl();
        }

        return str_starts_with($path, '//') ? $path : Storage::url($path);
    }

    /**
     * Generate a mock image URL using Picsum Photos.
     */
    protected function getMockImageUrl(): string
    {
        if ($this->isVisual()) {
            return "https://picsum.photos/{$this->width}/{$this->height}?random={$this->created_at->timestamp}";
        }

        // Extract UUID from path for consistent color per media record
        preg_match('/dev-([a-f0-9-]+)\./', basename($this->path), $matches);
        $seed = $matches[1] ?? 'default';

        // Generate a consistent color based on the seed
        $hue = abs(crc32($seed)) % 360;
        $color = sprintf(
            '%02x%02x%02x',
            (int) (127 + 127 * cos(deg2rad($hue))),
            (int) (127 + 127 * cos(deg2rad($hue + 120))),
            (int) (127 + 127 * cos(deg2rad($hue + 240)))
        );

        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400' viewBox='0 0 400 400'%3E%3Crect width='100%25' height='100%25' fill='%23{$color}'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial,sans-serif' font-size='24' fill='%23ffffff' text-anchor='middle' dominant-baseline='central'%3E{$this->category->name}%3C/text%3E%3C/svg%3E";
    }
}
