<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Project;
use App\Services\MediaThumbnailService;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MediaSeeder extends Seeder
{
    private array $sampleFiles = [];

    public function __construct(private MediaThumbnailService $thumbnailService)
    {
        // ...
    }

    public function run(): void
    {
        $this->copySampleFilesToPublicStorage();
        $this->loadSampleFiles();

        Project::with('users')->get()->each(function (Project $project) {
            $count = rand(15, 50);
            $users = $project->users->pluck('id')->toArray();

            // Flatten all sample files into a single array
            $allSamples = array_merge(
                $this->sampleFiles['image'],
                $this->sampleFiles['video'],
                $this->sampleFiles['document'],
                $this->sampleFiles['audio']
            );

            // Pick random subset without repetition
            $selectedSamples = collect($allSamples)->shuffle()->take($count)->all();

            // Create Media records for each selected sample
            foreach ($selectedSamples as $file) {
                Media::create([
                    'project_id'  => $project->id,
                    'owner_id'    => $users[array_rand($users)],
                    'path'        => $file['path'],
                    'thumbnail'   => $file['thumbnail'],
                    'description' => $this->getRandomDescription($file['category']),
                    'alt_text'    => rand(0, 1) ? $this->getRandomAltText($file['category']) : null,
                    'width'       => $file['width'],
                    'height'      => $file['height'],
                    'category'    => $file['category'],
                    'mime_type'   => $file['mime_type'],
                    'file_size'   => $file['file_size'],
                ]);
            }
        });
    }

    private function copySampleFilesToPublicStorage(): void
    {
        $sourceDir = storage_path('seeders/media');
        $publicDisk = Storage::disk('public');

        $categories = [ 'images', 'videos', 'documents', 'audio', 'thumbnails' ];

        foreach ($categories as $folder) {
            $sourcePath = "$sourceDir/$folder";

            foreach (File::files($sourcePath) as $file) {
                $filename = $file->getFilename();
                $destination = $folder === 'thumbnails' ? "thumbnails/$filename" : "media/$filename";

                // Copy file if it doesn't exist
                if (! $publicDisk->exists($destination)) {
                    $publicDisk->put($destination, File::get($file->getPathname()));
                }
            }
        }
    }

    private function loadSampleFiles(): void
    {
        $mediaPath = storage_path('app/public/media');

        $this->sampleFiles = [
            'image'    => [],
            'video'    => [],
            'document' => [],
            'audio'    => [],
        ];

        foreach (File::files($mediaPath) as $file) {
            $filename = $file->getFilename();
            $extension = strtolower($file->getExtension());
            $relativePath = "media/$filename";
            $thumbnailPath = 'thumbnails/thumb-' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';

            $category = $this->getCategory($extension);

            $fileInfo = [
                'path'      => $relativePath,
                'thumbnail' => in_array($category, ['image', 'video']) ? $thumbnailPath : null,
                'mime_type' => $this->getMimeType($extension),
                'file_size' => $file->getSize(),
                'width'     => null,
                'height'    => null,
                'category'  => $category,
            ];

            // Get dimensions for images
            if ($category === 'image' && in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                try {
                    [$width, $height] = getimagesize($file->getPathname());
                    $fileInfo['width'] = $width;
                    $fileInfo['height'] = $height;
                } catch (Exception $e) {
                    // Skip if can't get dimensions
                }
            }

            $this->sampleFiles[$category][] = $fileInfo;
        }
    }

    private function getCategory(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => 'image',
            'mp4', 'webm', 'avi', 'mov' => 'video',
            'mp3', 'wav', 'ogg', 'aac', 'flac' => 'audio',
            default => 'document',
        };
    }

    private function getMimeType(string $extension): string
    {
        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
            'webp'  => 'image/webp',
            'svg'   => 'image/svg+xml',
            'mp4'   => 'video/mp4',
            'webm'  => 'video/webm',
            'avi'   => 'video/avi',
            'mov'   => 'video/quicktime',
            'mp3'   => 'audio/mpeg',
            'wav'   => 'audio/wav',
            'ogg'   => 'audio/ogg',
            'aac'   => 'audio/aac',
            'flac'  => 'audio/flac',
            'pdf'   => 'application/pdf',
            'doc'   => 'application/msword',
            'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'   => 'application/vnd.ms-excel',
            'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'   => 'application/vnd.ms-powerpoint',
            'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt'   => 'text/plain',
            'csv'   => 'text/csv',
            'md'    => 'text/markdown',
            default => 'application/octet-stream',
        };
    }

    private function getRandomDescription(string $category): string
    {
        $descriptions = [
            'image' => [
                'Avance de obra - Semana',
                'Foto de inspección del predio',
                'Evento comunitario',
                'Mejora de instalaciones',
                'Hito del proyecto',
                'Reunión de equipo',
                'Instalación de equipamiento',
                'Inspección de seguridad',
            ],
            'video' => [
                'Video resumen del proyecto',
                'Timelapse de construcción',
                'Presentación comunitaria',
                'Capacitación en seguridad',
                'Demostración de equipamiento',
            ],
            'document' => [
                'Especificaciones del proyecto',
                'Actas de reunión',
                'Informe de avance',
                'Resumen presupuestario',
                'Documentación técnica',
                'Lineamientos de seguridad',
            ],
            'audio' => [
                'Grabación de reunión comunitaria',
                'Actualización del proyecto',
                'Grabación de entrevista',
                'Notas de audio',
            ],
        ];

        $base = $descriptions[$category][array_rand($descriptions[$category])];

        return $base . ' ' . rand(1, 50);
    }

    private function getRandomAltText(string $category): string
    {
        $altTexts = [
            'image' => [
                'Vista general del predio',
                'Miembros del equipo trabajando',
                'Equipamiento en uso',
                'Instalación completada',
                'Reunión comunitaria',
            ],
            'video' => [
                'Video mostrando avance del proyecto',
                'Grabación de evento comunitario',
            ],
            'document' => [
                'Documento con detalles del proyecto',
            ],
            'audio' => [
                'Grabación de audio',
            ],
        ];

        return $altTexts[$category][array_rand($altTexts[$category])];
    }
}
