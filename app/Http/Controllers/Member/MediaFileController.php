<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves project files to the people of the project only (the route sits behind
 * ResolveProject). Never inline anything that could run in the page.
 */
class MediaFileController extends Controller
{
    public function file(Project $project, Media $media): StreamedResponse
    {
        $disposition = $media->isVisual() || str_starts_with($media->mime_type, 'audio/') || $media->mime_type === 'application/pdf'
            ? 'inline'
            : 'attachment';

        return Storage::disk(Media::DISK)->response($media->path, $this->downloadName($media), [
            'Content-Type'           => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=86400',
        ], $disposition);
    }

    public function thumbnail(Project $project, Media $media): StreamedResponse
    {
        abort_unless($media->thumbnail, 404);

        return Storage::disk(Media::DISK)->response($media->thumbnail, null, [
            'Content-Type'           => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, max-age=604800',
        ]);
    }

    private function downloadName(Media $media): string
    {
        return str($media->description)->slug()->limit(60, '') . '.' . pathinfo($media->path, PATHINFO_EXTENSION);
    }
}
