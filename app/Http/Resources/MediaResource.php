<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MediaResource extends JsonResource
{
    public function toArray(Request $_): array
    {
        return [
            ...$this->commonResourceData(),

            'path'        => $this->path,
            'url'         => $this->getUrl(),
            'description' => $this->description,
            'alt_text'    => $this->alt_text,
            'width'       => $this->width,
            'height'      => $this->height,
            'dimensions'  => $this->dimensions(),
            'category'    => $this->category,
            'mime_type'   => $this->mime_type,
            'file_size'   => $this->file_size,
            'is_image'    => $this->isImage(),

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
        // For non-images, return a placeholder
        if (! $this->isImage()) {
            return 'https://via.placeholder.com/200x200/cccccc/666666?text=' . ucfirst($this->category);
        }

        // For images, use the stored dimensions or reasonable defaults
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
}
