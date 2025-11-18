<?php

namespace App\Services;

use App\Models\Media;
use Exception;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\TimeCode;
use Illuminate\Support\Facades\Storage;

class MediaThumbnailService
{
    /**
     * Generate a thumbnail for any media file type and return its URL.
     */
    public function generateThumbnail(Media $media): string
    {
        try {
            return match(true) {
                $media->isImage() => $this->imageThumbnail($media),
                $media->isVideo() => $this->videoThumbnail($media),
                $media->isAudio() => $this->audioThumbnail($media),
                default           => $this->genericThumbnailIcon($media),
            };
        } catch (Exception $e) {
            logger()->error('Thumbnail generation failed', [
                'media_id' => $media->id,
                'error'    => $e->getMessage()
            ]);

            return '/thumbnails/unknown.png';
        }
    }

    /**
     * Delete the thumbnail file for a media item.
     */
    public function deleteThumbnail(Media $media): bool
    {
        // Only delete if it's a generated thumbnail (not a generic icon or symlink)
        if (! str_contains($media->thumbnail, '/thumbnails/thumb_')) {
            return true;
        }

        $thumbnailPath = str_replace(Storage::url(''), '', $media->thumbnail);

        return Storage::disk('public')->delete($thumbnailPath);
    }

    /**
     * Handle image thumbnail generation (800x800 if original is larger, symlink if smaller).
     */
    private function imageThumbnail(Media $media): string
    {
        $sourcePath = Storage::disk('public')->path($media->path);
        $imageInfo = getimagesize($sourcePath);

        if ($imageInfo === false) {
            return $this->genericThumbnailIcon($media);
        }

        [$width, $height] = $imageInfo;
        $maxDimension = 800;

        // If image is small enough, use original image
        if ($width <= $maxDimension && $height <= $maxDimension) {
            return $media->url();
        }

        // Generate resized thumbnail
        $mediaHash = pathinfo($media->path, PATHINFO_FILENAME);
        $thumbnailFilename = 'thumb_' . $mediaHash . '.jpg';
        $thumbnailPath = "thumbnails/{$thumbnailFilename}";
        $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

        // Ensure thumbnails directory exists
        Storage::disk('public')->makeDirectory('thumbnails');

        if (! $this->createOptimizedImageThumbnail($sourcePath, $thumbnailFullPath, $maxDimension)) {
            return $this->genericThumbnailIcon($media);
        }

        return $thumbnailPath;
    }

    /**
     * Handle video thumbnail generation with play button overlay.
     */
    private function videoThumbnail(Media $media): string
    {
        try {
            $sourcePath = Storage::disk('public')->path($media->path);
            $mediaHash = pathinfo($media->path, PATHINFO_FILENAME);
            $thumbnailFilename = 'thumb_' . $mediaHash . '.jpg';
            $thumbnailPath = "thumbnails/{$thumbnailFilename}";
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

            // Ensure thumbnails directory exists
            Storage::disk('public')->makeDirectory('thumbnails');

            // Generate video thumbnail with play button
            if ($this->generateVideoThumbnailWithPlayButton($sourcePath, $thumbnailFullPath)) {
                return $thumbnailPath;
            }
        } catch (Exception $e) {
            logger()->error('Video thumbnail generation failed', [
                'media_id' => $media->id,
                'error'    => $e->getMessage(),
            ]);
        }

        // Create generic video thumbnail as fallback
        return $this->createGenericVideoThumbnail($media);
    }

    /**
     * Handle audio thumbnail using generic audio icon.
     */
    private function audioThumbnail(Media $media): string
    {
        return '/thumbnails/audio.jpg';
    }

    /**
     * Find the appropriate icon for a generic file type.
     *
     * Priority: extension-based -> mime-type-based -> unknown
     */
    private function genericThumbnailIcon(Media $media): string
    {
        $extension = strtolower(pathinfo($media->path, PATHINFO_EXTENSION));
        $extensionIconPath = public_path("thumbnails/extensions/{$extension}.png");
        $mimeIconPath = public_path("thumbnails/mimetypes/{$media->mime_type}.png");

        return match (true) {
            file_exists($extensionIconPath) => "/thumbnails/extensions/{$extension}.png",
            file_exists($mimeIconPath)      => "/thumbnails/mimetypes/{$media->mime_type}.png",
            default                         => '/thumbnails/unknown.png',
        };
    }

    /**
     * Create an optimized image thumbnail using GD.
     */
    private function createOptimizedImageThumbnail(string $sourcePath, string $thumbnailPath, int $maxDimension): bool
    {
        try {
            if (! $imageInfo = getimagesize($sourcePath)) {
                return false;
            }

            [$width, $height, $type] = $imageInfo;

            // Calculate new dimensions maintaining aspect ratio
            $ratio = min($maxDimension / $width, $maxDimension / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $sourceImage = match($type) {
                IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
                IMAGETYPE_PNG  => imagecreatefrompng($sourcePath),
                IMAGETYPE_GIF  => imagecreatefromgif($sourcePath),
                IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
                default        => null,
            };

            if (! $sourceImage) {
                return false;
            }

            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // Handle transparency for PNG/GIF
            if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }

            imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Save as JPEG for consistency
            $result = imagejpeg($thumbnail, $thumbnailPath, 85);

            imagedestroy($sourceImage);
            imagedestroy($thumbnail);

            return $result;
        } catch (Exception $e) {
            logger()->error('Image thumbnail creation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Create a generic video thumbnail when FFmpeg processing fails.
     */
    private function createGenericVideoThumbnail(Media $media): string
    {
        try {
            $size = 800;
            $mediaHash = pathinfo($media->path, PATHINFO_FILENAME);
            $thumbnailFilename = 'thumb_' . $mediaHash . '.jpg';
            $thumbnailPath = "thumbnails/{$thumbnailFilename}";
            $thumbnailFullPath = Storage::disk('public')->path($thumbnailPath);

            // Ensure thumbnails directory exists
            Storage::disk('public')->makeDirectory('thumbnails');

            $image = imagecreatetruecolor($size, $size);

            // Create gradient background (dark gray to black)
            for ($y = 0; $y < $size; $y++) {
                $gradientValue = (int)(40 - ($y / $size * 30)); // 40 to 10
                $color = imagecolorallocate($image, $gradientValue, $gradientValue, $gradientValue);
                imageline($image, 0, $y, $size, $y, $color);
            }

            // Create play button circle
            $centerX = $size / 2;
            $centerY = $size / 2;
            $circleSize = $size * 0.3;

            // Semi-transparent white circle
            $circleColor = imagecolorallocatealpha($image, 255, 255, 255, 30);
            imagefilledellipse($image, $centerX, $centerY, $circleSize, $circleSize, $circleColor);

            // White play triangle
            $triangleSize = $circleSize * 0.4;
            $triangleX = $centerX - $triangleSize / 6; // Offset slightly right for visual balance
            $triangleY = $centerY;

            $triangle = [
                $triangleX - $triangleSize / 2, $triangleY - $triangleSize / 2,
                $triangleX - $triangleSize / 2, $triangleY + $triangleSize / 2,
                $triangleX + $triangleSize / 2, $triangleY
            ];

            $whiteColor = imagecolorallocate($image, 255, 255, 255);
            imagefilledpolygon($image, $triangle, 3, $whiteColor);

            // Add "VIDEO" text at bottom
            $textColor = imagecolorallocate($image, 200, 200, 200);
            $fontSize = 5; // Built-in font size
            $text = 'VIDEO';
            $textWidth = strlen($text) * imagefontwidth($fontSize);
            $textX = ($size - $textWidth) / 2;
            $textY = $size - 60;
            imagestring($image, $fontSize, $textX, $textY, $text, $textColor);

            $result = imagejpeg($image, $thumbnailFullPath, 85);
            imagedestroy($image);
        } catch (Exception $e) {
            logger()->error('Generic video thumbnail creation failed', ['error' => $e->getMessage()]);
        }

        if (! $result) {
            return $this->genericThumbnailIcon($media);
        }

        return $thumbnailPath;
    }

    /**
     * Generate video thumbnail with intelligent scene detection and play button overlay.
     */
    private function generateVideoThumbnailWithPlayButton(string $sourcePath, string $thumbnailPath): bool
    {
        try {
            $video = FFMpeg::create([
                'ffmpeg.binaries'  => '/usr/bin/ffmpeg',
                'ffprobe.binaries' => '/usr/bin/ffprobe',
            ])->open($sourcePath);

            $duration = $video->getFormat()->get('duration');

            // Find the best frame using scene detection
            $bestTimecode = $this->findBestVideoFrame($video, $duration);

            // Extract frame
            $frame = $video->frame($bestTimecode);
            $tempFramePath = $thumbnailPath . '.temp.jpg';
            $frame->save($tempFramePath);

            // Add play button overlay
            if ($this->addPlayButtonOverlay($tempFramePath, $thumbnailPath)) {
                unlink($tempFramePath);
                return true;
            }

            unlink($tempFramePath);
            return false;
        } catch (Exception $e) {
            logger()->error('Video thumbnail with play button failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Find the best frame in a video using quality analysis.
     */
    private function findBestVideoFrame($video, float $duration): TimeCode
    {
        $samplePoints = [
            $duration * 0.1,  // 10% into video
            $duration * 0.25, // 25% into video
            $duration * 0.5,  // 50% into video
            $duration * 0.75, // 75% into video
        ];

        $bestScore = -1;
        $bestTime = $samplePoints[1]; // Default to 25% as fallback

        foreach ($samplePoints as $timePoint) {
            try {
                $timecode = TimeCode::fromSeconds($timePoint);
                $frame = $video->frame($timecode);

                // Save to temporary file for analysis
                $tempPath = sys_get_temp_dir() . '/frame_' . uniqid() . '.jpg';
                $frame->save($tempPath);

                // Analyze frame quality
                $score = $this->analyzeFrameQuality($tempPath);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestTime = $timePoint;
                }

                if (file_exists($tempPath)) {
                    unlink($tempPath);
                }
            } catch (Exception $e) {
                // If this frame fails, continue with others
                logger()->debug('Frame analysis failed at ' . $timePoint . 's', ['error' => $e->getMessage()]);
            }
        }

        return TimeCode::fromSeconds($bestTime);
    }

    /**
     * Analyze the quality of a video frame.
     * Returns a score where higher = better quality.
     */
    private function analyzeFrameQuality(string $framePath): float
    {
        if (! $image = imagecreatefromjpeg($framePath)) {
            return 0;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $totalBrightness = 0;
        $brightPixels = 0;
        $darkPixels = 0;

        // Sample pixels for performance (every 10th pixel)
        for ($x = 0; $x < $width; $x += 10) {
            for ($y = 0; $y < $height; $y += 10) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Calculate brightness (luminance)
                $brightness = 0.299 * $r + 0.587 * $g + 0.114 * $b;
                $totalBrightness += $brightness;

                if ($brightness < 30) {
                    $darkPixels++;
                }
                if ($brightness > 200) {
                    $brightPixels++;
                }
            }
        }

        imagedestroy($image);

        $sampledPixels = ($width / 10) * ($height / 10);
        $avgBrightness = $totalBrightness / $sampledPixels;
        $darkRatio = $darkPixels / $sampledPixels;
        $brightRatio = $brightPixels / $sampledPixels;

        // Score calculation:
        // - Penalize very dark images (black screens, fade-ins)
        // - Penalize very bright images (white screens, overexposure)
        // - Reward moderate brightness with good contrast
        $score = 0;

        // Brightness score (prefer 50-150 range)
        if ($avgBrightness >= 50 && $avgBrightness <= 150) {
            $score += 50;
        } else {
            $score += max(0, 50 - abs($avgBrightness - 100) / 2);
        }

        // Contrast score (penalize too many dark or bright pixels)
        $score -= $darkRatio * 30;  // Penalize dark frames
        $score -= $brightRatio * 20; // Penalize overexposed frames

        // Avoid completely black or white frames
        if ($avgBrightness < 10 || $avgBrightness > 245) {
            $score -= 100;
        }

        return max(0, $score);
    }

    /**
     * Add a play button overlay to a video thumbnail.
     */
    private function addPlayButtonOverlay(string $sourcePath, string $outputPath): bool
    {
        try {
            if (! $image = imagecreatefromjpeg($sourcePath)) {
                return false;
            }

            $width = imagesx($image);
            $height = imagesy($image);

            // Calculate play button size (15% of smallest dimension)
            $buttonSize = (int)(min($width, $height) * 0.15);
            $buttonX = (int)(($width - $buttonSize) / 2);
            $buttonY = (int)(($height - $buttonSize) / 2);

            // Create semi-transparent black circle
            $circleColor = imagecolorallocatealpha($image, 0, 0, 0, 50);
            imagefilledellipse(
                $image,
                $buttonX + $buttonSize / 2,
                $buttonY + $buttonSize / 2,
                $buttonSize,
                $buttonSize,
                $circleColor
            );

            // Create white triangle (play symbol)
            $triangleSize = $buttonSize * 0.4;
            $triangleX = $buttonX + $buttonSize / 2 - $triangleSize / 3;
            $triangleY = $buttonY + $buttonSize / 2;

            $triangle = [
                $triangleX, $triangleY - $triangleSize / 2,
                $triangleX, $triangleY + $triangleSize / 2,
                $triangleX + $triangleSize, $triangleY
            ];

            $whiteColor = imagecolorallocate($image, 255, 255, 255);
            imagefilledpolygon($image, $triangle, 3, $whiteColor);

            $result = imagejpeg($image, $outputPath, 85);
            imagedestroy($image);

            return $result;
        } catch (Exception $e) {
            logger()->error('Play button overlay failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
