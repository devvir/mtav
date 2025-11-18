<?php

namespace App\Services;

use App\Enums\MediaCategory;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Store an uploaded file and create a media record.
     */
    public function storeFile(UploadedFile $file, array $attributes = []): Media
    {
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('media', $filename, 'public');

        // Get file metadata
        $metadata = $this->extractFileMetadata($file);

        return Media::create([
            'path'      => $path,
            'width'     => $metadata['width'] ?? null,
            'height'    => $metadata['height'] ?? null,
            'category'  => $metadata['category'],
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            ...$attributes,
        ]);
    }

    /**
     * Delete a media file from storage (use for force delete only).
     * For soft deletes, keep the file so it can be restored.
     */
    public function deleteFile(Media $media): bool
    {
        if (Storage::disk('public')->exists($media->path)) {
            return Storage::disk('public')->delete($media->path);
        }

        return true; // Consider non-existent file as successfully deleted
    }

    /**
     * Force delete a media record and its file.
     * This permanently removes both the database record and the physical file.
     */
    public function forceDeleteMedia(Media $media): bool
    {
        $fileDeleted = $this->deleteFile($media);
        $media->forceDelete();

        return $fileDeleted;
    }

    /**
     * Extract metadata from an uploaded file.
     */
    public function extractFileMetadata(UploadedFile $file): array
    {
        $metadata = [
            'category' => $this->determineCategory($file->getMimeType()),
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
    public function determineCategory(string $mimeType): MediaCategory
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
