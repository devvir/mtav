<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves project files to the people of the project only (the route sits behind
 * ResolveProject). Never inline anything that could run in the page.
 */
class MediaFileController extends Controller
{
    /**
     * Files and thumbnails are asked for each time (a browser keeps them only to ask whether
     * they changed): someone who left the project, or the next person on the device, never
     * sees one from a cache. A file never changes once shared, so its path names its version.
     */
    public function file(Request $request, Project $project, Media $media): Response|StreamedResponse
    {
        abort_unless($media->isOn(), 404);

        $disposition = $media->isVisual() || str_starts_with($media->mime_type, 'audio/') || $media->mime_type === 'application/pdf'
            ? 'inline'
            : 'attachment';

        $headers = $this->askedEachTime($media->path);

        if ($this->unchanged($request, $headers)) {
            return response()->noContent(304, $headers);
        }

        return Storage::disk(Media::DISK)->response($media->path, $this->downloadName($media), [
            'Content-Type' => $media->mime_type,
            ...$headers,
        ], $disposition);
    }

    public function thumbnail(Request $request, Project $project, Media $media): Response|StreamedResponse
    {
        abort_unless($media->thumbnail && $media->isOn(), 404);

        $headers = $this->askedEachTime($media->thumbnail);

        if ($this->unchanged($request, $headers)) {
            return response()->noContent(304, $headers);
        }

        return Storage::disk(Media::DISK)->response($media->thumbnail, null, ['Content-Type' => 'image/jpeg', ...$headers]);
    }

    /** @return array<string, string> */
    private function askedEachTime(string $path): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-cache',
            'ETag'                   => '"' . md5($path) . '"',
        ];
    }

    /** @param array<string, string> $headers */
    private function unchanged(Request $request, array $headers): bool
    {
        return in_array($headers['ETag'], $request->getETags(), true);
    }

    private function downloadName(Media $media): string
    {
        return str($media->description)->slug()->limit(60, '') . '.' . pathinfo($media->path, PATHINFO_EXTENSION);
    }
}
