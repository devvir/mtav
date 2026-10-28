<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Process;
use ZipArchive;

/**
 * Shared files of every kind for the demo: photos, a video, audio and documents, made on
 * the spot (no binaries in the repository) and stored the way an upload is, thumbnails
 * and all. The video and the audio need ffmpeg (the app's image has it); without it,
 * they are left out.
 */
class DemoMedia
{
    private string $dir;

    public function __construct(private MediaService $media)
    {
        $this->dir = sys_get_temp_dir() . '/mtav-demo-media-' . uniqid();
        mkdir($this->dir);
    }

    public function __destruct()
    {
        array_map('unlink', glob("{$this->dir}/*") ?: []);
        @rmdir($this->dir);
    }

    /** Shares them in a project, by its people in turn */
    public function share(Project $project, User ...$people): void
    {
        $files = [
            ['obra-bloque-a.jpg', fn ($path) => $this->photo($path, [96, 140, 200], [214, 168, 120], 5), 'Avance del bloque A: ya están las losas del segundo piso.'],
            ['jornada-encofrado.jpg', fn ($path) => $this->photo($path, [232, 150, 96], [180, 120, 90], 3), 'Jornada de encofrado del sábado, turno de la mañana.'],
            ['terreno-vista-norte.png', fn ($path) => $this->photo($path, [120, 180, 220], [120, 160, 90], 2, png: true), 'El terreno visto desde el norte, antes de empezar.'],
            ['fachada-propuesta.jpg', fn ($path) => $this->photo($path, [40, 50, 90], [200, 200, 210], 4), 'Propuesta de fachada que presentó el arquitecto.'],
            ['recorrida-obra.mp4', fn ($path) => $this->video($path), 'Recorrida por la obra en un minuto (versión corta).'],
            ['asamblea-resumen.mp3', fn ($path) => $this->audio($path, 'libmp3lame'), 'Resumen grabado de la última asamblea.'],
            ['mensaje-comision.m4a', fn ($path) => $this->audio($path, 'aac', [220, 277.2, 329.6]), 'Mensaje de la comisión de obra para quienes no pudieron venir.'],
            ['reglamento-interno.pdf', fn ($path) => $this->pdf($path, 'Reglamento interno', [
                'Capitulo 1. De las asambleas.',
                'La asamblea general se reune una vez por mes.',
                'Capitulo 2. De las horas de trabajo.',
                'Cada familia aporta 21 horas semanales en obra.',
            ]), 'Reglamento interno aprobado en asamblea.'],
            ['acta-asamblea.txt', fn ($path) => file_put_contents($path, "Acta de la asamblea general\n\nAsistentes: 34 familias.\n\n1. Avance de obra: el bloque A va según lo previsto.\n2. Cuotas: se mantienen hasta fin de año.\n3. Sorteo: se fija la fecha para el mes próximo.\n"), 'Acta de la asamblea general.'],
            ['presupuesto-materiales.docx', fn ($path) => $this->docx($path, 'Presupuesto de materiales', [
                'Hormigón para losas del bloque B.',
                'Hierro para columnas y vigas.',
                'Aberturas: ventanas y puertas exteriores.',
            ]), 'Presupuesto de materiales para el segundo semestre.'],
        ];

        foreach ($files as $index => [$name, $make, $description]) {
            $path = "{$this->dir}/{$name}";
            if ($make($path) === false || ! is_file($path)) {
                continue;
            }
            $file = new UploadedFile($path, $name, mime_content_type($path) ?: null, null, true);
            $this->media->store($project, $people[$index % count($people)], [$file], $description);
        }
    }

    /** A building against the sky, in a palette of its own */
    private function photo(string $path, array $sky, array $walls, int $floors, bool $png = false): bool
    {
        [$width, $height] = [1600, 1000];
        $image = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            $shade = 1 - 0.5 * $y / $height;
            imageline($image, 0, $y, $width, $y, imagecolorallocate($image, ...array_map(fn ($c) => (int) min(255, $c * $shade + 40 * (1 - $shade)), $sky)));
        }
        imagefilledrectangle($image, 0, 820, $width, $height, imagecolorallocate($image, 110, 100, 85));

        $wall = imagecolorallocate($image, ...$walls);
        $window = imagecolorallocate($image, 60, 70, 90);
        foreach ([[180, 620], [880, 520]] as [$left, $blockWidth]) {
            $top = 820 - $floors * 130;
            imagefilledrectangle($image, $left, $top, $left + $blockWidth, 820, $wall);
            for ($floor = 0; $floor < $floors; $floor++) {
                for ($x = $left + 40; $x < $left + $blockWidth - 60; $x += 110) {
                    imagefilledrectangle($image, $x, $top + 30 + $floor * 130, $x + 60, $top + 100 + $floor * 130, $window);
                }
            }
        }

        return $png ? imagepng($image, $path) : imagejpeg($image, $path, 85);
    }

    private function video(string $path): bool
    {
        return Process::run([
            'ffmpeg', '-v', 'error', '-y',
            '-f', 'lavfi', '-i', 'gradients=s=960x540:d=6:speed=0.03:rate=24',
            '-f', 'lavfi', '-i', 'sine=frequency=330:duration=6',
            '-c:v', 'libx264', '-pix_fmt', 'yuv420p', '-c:a', 'aac', '-shortest', $path,
        ])->successful();
    }

    /** A few seconds of a chord */
    private function audio(string $path, string $codec, array $notes = [261.6, 329.6, 392]): bool
    {
        $wave = implode('+', array_map(fn ($note) => "0.2*sin(2*PI*{$note}*t)", $notes));

        return Process::run([
            'ffmpeg', '-v', 'error', '-y', '-f', 'lavfi', '-i', "aevalsrc={$wave}:s=44100:d=6",
            '-c:a', $codec, $path,
        ])->successful();
    }

    /** A one-page PDF with a title and lines of text */
    private function pdf(string $path, string $title, array $lines): bool
    {
        $text = "BT /F1 20 Tf 72 760 Td ({$title}) Tj /F1 12 Tf 0 -36 Td";
        foreach ($lines as $line) {
            $text .= " ({$line}) Tj 0 -20 Td";
        }
        $text .= ' ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length ' . strlen($text) . " >>\nstream\n{$text}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1) . " 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return file_put_contents($path, $pdf) !== false;
    }

    /** A Word document: a title and paragraphs */
    private function docx(string $path, string $title, array $paragraphs): bool
    {
        $body = '<w:p><w:pPr><w:pStyle w:val="Title"/></w:pPr><w:r><w:t>' . e($title) . '</w:t></w:r></w:p>'
            . implode('', array_map(fn ($text) => '<w:p><w:r><w:t>' . e($text) . '</w:t></w:r></w:p>', $paragraphs));

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE) !== true) {
            return false;
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $body . '</w:body></w:document>');

        return $zip->close();
    }
}
