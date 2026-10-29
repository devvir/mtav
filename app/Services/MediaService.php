<?php

namespace App\Services;

use App\Enums\MediaCategory;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use FFMpeg\FFProbe;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Stores files shared in a project on the private media disk.
 */
class MediaService
{
    /**
     * Accepted types, by category. SVG is deliberately absent (it can carry scripts).
     */
    public const MIME_TYPES = [
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video' => ['video/mp4', 'video/webm', 'video/quicktime'],
        // audio/x-m4a: what phones' voice memos are reported as
        'audio'    => ['audio/mpeg', 'audio/mp4', 'audio/x-m4a', 'audio/ogg', 'audio/wav', 'audio/x-wav', 'audio/webm'],
        'document' => [
            'application/pdf',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
        ],
    ];

    /**
     * The most pixels an image may have: a small file can claim many more, and opening it
     * (its preview) would take more memory than the server has
     */
    public const MAX_MEGAPIXELS = 50;

    public function __construct(private MediaThumbnailService $thumbnails)
    {
    }

    /** Whether a file is an image of more pixels than can be opened (unknown: not one) */
    public static function tooManyPixels(string $path): bool
    {
        $size = @getimagesize($path);

        return $size && $size[0] * $size[1] > self::MAX_MEGAPIXELS * 1_000_000;
    }

    /** The largest file a project takes: its limit, within what PHP accepts at all */
    public static function maxFileKb(Project $project): int
    {
        $limit = 1024 * ($project->limit('media_file_mb') ?? config('limits.media_file_max_mb'));

        return (int) min($limit, UploadedFile::getMaxFilesize() / 1024);
    }

    /** The most one share may carry, all its files together (PHP's request limit, less room for the rest) */
    public static function maxBatchKb(): int
    {
        $post = (string) ini_get('post_max_size');
        $bytes = (int) $post * match (strtoupper(substr($post, -1))) {
            'G'     => 1024 ** 3,
            'M'     => 1024 ** 2,
            'K'     => 1024,
            default => 1,
        };

        return (int) max(1024, $bytes / 1024 - 1024);
    }

    /**
     * @param array<UploadedFile> $files
     *
     * @return array<Media>
     *
     * @throws ValidationException when the project's storage would be exceeded
     */
    public function store(Project $project, User $owner, array $files, string $description): array
    {
        $this->ensureStorageAvailable($project, $files);

        return array_map(fn (UploadedFile $file) => $this->storeOne($project, $owner, $file, $description), $files);
    }

    public function delete(Media $media): void
    {
        Storage::disk(Media::DISK)->delete(array_filter([$media->path, $media->thumbnail]));

        $media->delete();
    }

    public static function categoryOf(string $mimeType): ?MediaCategory
    {
        foreach (self::MIME_TYPES as $category => $types) {
            if (in_array($mimeType, $types)) {
                return MediaCategory::from($category);
            }
        }

        return null;
    }

    private function storeOne(Project $project, User $owner, UploadedFile $file, string $description): Media
    {
        $mimeType = $file->getMimeType();
        $extension = $file->guessExtension() ?: 'bin';

        $path = $file->storeAs("projects/{$project->id}", Str::uuid() . ".{$extension}", Media::DISK);

        $media = new Media([
            'project_id'  => $project->id,
            'owner_id'    => $owner->id,
            'category'    => self::categoryOf($mimeType),
            'path'        => $path,
            'description' => $description,
            'mime_type'   => $mimeType,
            'file_size'   => $file->getSize(),
            ...$this->dimensions($file, $mimeType),
        ]);

        $media->thumbnail = $this->thumbnails->generate($media);
        $media->save();

        return $media;
    }

    private function ensureStorageAvailable(Project $project, array $files): void
    {
        $quotaMb = $project->limit('media_storage_mb');

        if ($quotaMb === null) {
            return;
        }

        $used = $project->media()->sum('file_size');
        $incoming = array_sum(array_map(fn (UploadedFile $file) => $file->getSize(), $files));

        if ($used + $incoming > $quotaMb * 1024 * 1024) {
            throw ValidationException::withMessages([
                'files' => __('validation.limit_storage', ['max' => $quotaMb]),
            ]);
        }
    }

    /**
     * @return array{width?: int, height?: int}
     */
    private function dimensions(UploadedFile $file, string $mimeType): array
    {
        try {
            if (str_starts_with($mimeType, 'image/') && ($size = @getimagesize($file->getPathname()))) {
                return ['width' => $size[0], 'height' => $size[1]];
            }

            if (str_starts_with($mimeType, 'video/')) {
                $stream = FFProbe::create()->streams($file->getPathname())->videos()->first();

                return $stream ? ['width' => $stream->get('width'), 'height' => $stream->get('height')] : [];
            }
        } catch (Throwable $_) {
            /** Dimensions are a nicety: a file whose size cannot be read is still accepted */
        }

        return [];
    }
}
