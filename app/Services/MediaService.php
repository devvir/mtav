<?php

namespace App\Services;

use App\Enums\MediaCategory;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    public function __construct(
        private MediaThumbnailService $thumbnailService
    ) {
        // ...
    }

    /**
     * Create a new Media record and store its associated file and thumbnail.
     */
    public function create(UploadedFile $file, array $attributes = []): Media
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $metadata = $this->extractFileMetadata($file);

        $media = Media::make([
            'path'      => $file->storeAs('media', $filename, 'public'),
            'width'     => $metadata['width'] ?? null,
            'height'    => $metadata['height'] ?? null,
            'category'  => $metadata['category'],
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            ...Arr::only($attributes, ['description', 'alt_text', 'owner_id', 'project_id']),
        ]);

        $media->thumbnail = $this->thumbnailService->generateThumbnail($media);
        $media->save();

        return $media;
    }

    /**
     * Force delete a media record, its file and its thumbnail.
     */
    public function forceDeleteMedia(Media $media): bool
    {
        if (Storage::disk('public')->exists($media->path)) {
            return Storage::disk('public')->delete($media->path);
        }

        $this->thumbnailService->deleteThumbnail($media);

        return $media->forceDelete();
    }

    /**
     * Extract metadata from an uploaded file.
     */
    public function extractFileMetadata(UploadedFile $file): array
    {
        $metadata = [
            'category' => $this->mime2category($file->getMimeType()),
        ];

        // Add dimensions for images
        if ($metadata['category'] === MediaCategory::IMAGE) {
            $dimensions = $this->getImageDimensions($file);
            $metadata = array_merge($metadata, $dimensions);
        }

        return $metadata;
    }

    /**
     * Get image dimensions if the file is an image.
     */
    public function getImageDimensions(UploadedFile $file): array
    {
        if (!str_starts_with($file->getMimeType(), 'image/')) {
            return [];
        }

        try {
            $imageSize = getimagesize($file->getPathname());

            return [
                'width'  => $imageSize[0] ?? null,
                'height' => $imageSize[1] ?? null,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Determine the media category based on MIME type.
     */
    public function mime2category(string $mimeType): MediaCategory
    {
        if (str_starts_with($mimeType, 'image/')) {
            return MediaCategory::IMAGE;
        }

        if (str_starts_with($mimeType, 'video/')) {
            return MediaCategory::VIDEO;
        }

        if (str_starts_with($mimeType, 'audio/')) {
            return MediaCategory::AUDIO;
        }

        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
        ])) {
            return MediaCategory::DOCUMENT;
        }

        return MediaCategory::UNKNOWN; // Unrecognized or not-yet-supported types
    }
}
