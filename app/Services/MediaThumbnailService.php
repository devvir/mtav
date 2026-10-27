<?php

namespace App\Services;

use App\Enums\MediaCategory;
use App\Models\Media;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Small JPEG previews for images and videos, stored next to the original on the
 * media disk. Other categories have no thumbnail (the UI shows an icon instead).
 */
class MediaThumbnailService
{
    private const MAX_SIDE = 480;

    /**
     * @return string|null  Path of the thumbnail on the media disk
     */
    public function generate(Media $media): ?string
    {
        $thumbnail = preg_replace('/\.[^.\/]+$/', '', $media->path) . '.thumb.jpg';

        try {
            $created = match ($media->category) {
                MediaCategory::IMAGE => $this->fromImage($this->path($media->path), $this->path($thumbnail)),
                MediaCategory::VIDEO => $this->fromVideo($this->path($media->path), $this->path($thumbnail)),
                default              => false,
            };
        } catch (Throwable $e) {
            report($e);
            $created = false;
        }

        return $created ? $thumbnail : null;
    }

    private function fromImage(string $source, string $target): bool
    {
        $image = @imagecreatefromstring(file_get_contents($source));

        if (! $image) {
            return false;
        }

        [$width, $height] = [imagesx($image), imagesy($image)];
        $scale = min(1, self::MAX_SIDE / max($width, $height));

        $thumbnail = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        return $thumbnail && imagejpeg($thumbnail, $target, 82);
    }

    private function fromVideo(string $source, string $target): bool
    {
        $frame = "{$target}.frame.jpg";

        $video = FFMpeg::create([
            'ffmpeg.binaries'  => config('media.ffmpeg', '/usr/bin/ffmpeg'),
            'ffprobe.binaries' => config('media.ffprobe', '/usr/bin/ffprobe'),
        ])->open($source);

        $video->frame(TimeCode::fromSeconds(1))->save($frame);

        try {
            return $this->fromImage($frame, $target);
        } finally {
            @unlink($frame);
        }
    }

    private function path(string $relative): string
    {
        return Storage::disk(Media::DISK)->path($relative);
    }
}
