<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * A shared photo's or video's preview, and its size, made after the share: opening the file
 * (GD, ffprobe, ffmpeg) keeps nobody waiting. Until then its page shows its kind; the preview
 * comes in as a change of the file, live.
 */
class PreviewMedia implements ShouldQueue
{
    use Queueable;

    /** A file that could not be read once will not be the next time */
    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $mediaId)
    {
        $this->afterCommit();
    }

    public function handle(MediaService $media): void
    {
        ($file = Media::withTrashed()->find($this->mediaId)) && $media->preview($file);
    }
}
