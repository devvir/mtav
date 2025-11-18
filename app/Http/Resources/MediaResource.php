<?php

namespace App\Http\Resources;

use App\Models\Media;
use Illuminate\Http\Request;

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
            'url'            => $this->getUrl(),
            'description'    => $this->description,
            'alt_text'       => $this->alt_text,
            'width'          => $this->width,
            'height'         => $this->height,
            'dimensions'     => $this->dimensions(),
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

    protected function dimensions(): ?string
    {
        return ($this->width && $this->height)
            ? "{$this->width}x{$this->height}"
            : null;
    }

    /**
     * Get the URL for this media file, using mock images for dev files.
     */
    protected function getUrl(): string
    {
        // Check if this is a dev/mock file by looking for 'dev-' prefix in filename
        if ($this->isDevFile()) {
            return $this->getMockImageUrl();
        }

        // Return normal storage URL for real files
        return $this->url();
    }

    /**
     * Check if this is a development/mock file.
     */
    protected function isDevFile(): bool
    {
        return str_starts_with(basename($this->path), 'dev-');
    }

    /**
     * Generate a mock image URL using Picsum Photos.
     */
    protected function getMockImageUrl(): string
    {
        // For videos, treat them like images (they need thumbnails anyway)
        if ($this->isVisual()) {
            // Use the stored dimensions or reasonable defaults
            $width = $this->width ?? 800;
            $height = $this->height ?? 600;

            // Extract UUID from path for consistent image per media record
            $filename = basename($this->path);
            preg_match('/dev-([a-f0-9-]+)\./', $filename, $matches);
            $seed = $matches[1] ?? 'default';

            // Convert UUID to a simple number for Picsum seed
            $numericSeed = abs(crc32($seed));

            return "https://picsum.photos/{$width}/{$height}?random={$numericSeed}";
        }

        // For other file types (documents, archives), use a simple colored background
        $width = 400;
        $height = 400;

        // Extract UUID from path for consistent color per media record
        $filename = basename($this->path);
        preg_match('/dev-([a-f0-9-]+)\./', $filename, $matches);
        $seed = $matches[1] ?? 'default';

        // Generate a consistent color based on the seed
        $hue = abs(crc32($seed)) % 360;
        $color = sprintf(
            '%02x%02x%02x',
            (int)(127 + 127 * cos(deg2rad($hue))),
            (int)(127 + 127 * cos(deg2rad($hue + 120))),
            (int)(127 + 127 * cos(deg2rad($hue + 240)))
        );

        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='{$width}' height='{$height}' viewBox='0 0 {$width} {$height}'%3E%3Crect width='100%25' height='100%25' fill='%23{$color}'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial,sans-serif' font-size='24' fill='%23ffffff' text-anchor='middle' dominant-baseline='central'%3E" . strtoupper($this->category) . "%3C/text%3E%3C/svg%3E";
    }
}
