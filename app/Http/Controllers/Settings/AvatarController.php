<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\MediaService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * A person's picture: an image (checked by content), kept small and anew (512 px at most,
 * WebP), so it is light wherever it shows and carries nothing of the original's metadata
 * (where a photo was taken, the camera).
 */
class AvatarController extends Controller
{
    private const SIDE = 512;

    public function update(Request $request): RedirectResponse
    {
        $request->validate(['avatar' => [
            'required', 'image', 'mimes:jpeg,png,webp', 'max:8192',
            function (string $attribute, mixed $file, Closure $fail) {
                if ($file instanceof UploadedFile && MediaService::tooManyPixels($file->getPathname())) {
                    $fail(__('validation.image_too_large', ['max' => MediaService::MAX_MEGAPIXELS]));
                }
            },
        ]]);

        $user = $request->user();
        $previous = $user->avatar;

        // WebP where this PHP can write it, else JPEG: never an empty picture
        $webp = function_exists('imagewebp') && (imagetypes() & IMG_WEBP);
        $path = 'avatars/' . Str::random(40) . ($webp ? '.webp' : '.jpg');
        Storage::disk('public')->put($path, $this->small($request->file('avatar'), $webp));
        $user->update(['avatar' => $path]);

        $previous && Storage::disk('public')->delete($previous);

        return back()->with('success', __('flash.avatar_updated'));
    }

    /** The picture drawn anew, no side over SIDE, as WebP (or JPEG) */
    private function small(UploadedFile $file, bool $webp): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getPathname()));
        if (! $image) {
            throw ValidationException::withMessages(['avatar' => __('validation.image', ['attribute' => 'avatar'])]);
        }

        [$width, $height] = [imagesx($image), imagesy($image)];
        $scale = min(1, self::SIDE / max($width, $height));
        $small = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

        ob_start();
        $webp ? imagewebp($small, null, 85) : imagejpeg($small, null, 85);

        return (string) ob_get_clean();
    }
}
