<?php

namespace Tests\Browser\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Reads the files of a multipart request the way PHP does for a real one.
 *
 * The browser plugin's in-process server parses multipart bodies itself, and drops a
 * byte of binary files and keeps nested names flat (`files[0]`, so `files` arrives
 * empty). The raw body is still there: this reads the uploads from it again, byte for
 * byte, nested by name.
 */
class ReadUploadsLikePhp
{
    public function handle(Request $request, Closure $next): mixed
    {
        $boundary = preg_match('/boundary="?([^";]+)"?/', (string) $request->header('Content-Type'), $match)
            ? $match[1]
            : null;

        if ($boundary !== null && $request->files->count() > 0) {
            $request->files->replace($this->uploads($request->getContent(), $boundary));
        }

        return $next($request);
    }

    /**
     * @return array<string, mixed> The uploads, nested by their names
     */
    private function uploads(string $body, string $boundary): array
    {
        $uploads = [];
        $names = [];

        // Each part sits between "--boundary\r\n" and "\r\n--boundary"
        foreach (array_slice(explode("--{$boundary}", $body), 1, -1) as $part) {
            [$headers, $content] = explode("\r\n\r\n", substr($part, 2), 2);
            $content = substr($content, 0, -2);

            if (! preg_match('/name="([^"]*)"; filename="([^"]*)"/', $headers, $field)) {
                continue;
            }

            $type = preg_match('/Content-Type:\s*([^\r\n]+)/i', $headers, $match) ? trim($match[1]) : null;
            $path = tempnam(sys_get_temp_dir(), 'upload-');
            file_put_contents($path, $content);

            $names[] = $field[1] . '=' . count($uploads);
            $uploads[] = new UploadedFile($path, $field[2], $type, null, true);
        }

        parse_str(implode('&', $names), $nested);
        array_walk_recursive($nested, function (&$index) use ($uploads) {
            $index = $uploads[$index];
        });

        return $nested;
    }
}
