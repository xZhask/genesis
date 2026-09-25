<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Guarda una imagen subida en varios tamaños con GD (sin dependencias).
 * Corrige la orientación de las fotos del celular y guarda en WebP,
 * que pesa menos para quien navega con datos móviles.
 *
 * Devuelve la ruta base (p. ej. "posts/9f2c…"); cada tamaño queda en
 * "{base}-{ancho}.webp" dentro del disco público.
 */
class ImageResizer
{
    public const SIZES = ['lg' => 1200, 'sm' => 600];

    public static function store(UploadedFile $file, string $directory): string
    {
        $source = self::load($file);
        $base = trim($directory, '/').'/'.Str::uuid();

        foreach (self::SIZES as $width) {
            $resized = self::scale($source, $width);

            ob_start();
            imagewebp($resized, null, 80);
            Storage::disk('public')->put("{$base}-{$width}.webp", ob_get_clean());

            if ($resized !== $source) {
                imagedestroy($resized);
            }
        }

        imagedestroy($source);

        return $base;
    }

    public static function url(?string $base, string $size = 'lg'): ?string
    {
        return $base ? Storage::disk('public')->url("{$base}-".self::SIZES[$size].'.webp') : null;
    }

    public static function delete(?string $base): void
    {
        if ($base) {
            Storage::disk('public')->delete(array_map(fn ($w) => "{$base}-{$w}.webp", self::SIZES));
        }
    }

    private static function load(UploadedFile $file): \GdImage
    {
        $path = $file->getRealPath();
        $image = @imagecreatefromstring((string) file_get_contents($path));

        if (! $image) {
            throw new RuntimeException('No se pudo leer la imagen.');
        }

        // Las fotos del celular guardan la rotación en EXIF
        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            $orientation = @exif_read_data($path)['Orientation'] ?? 1;
            $image = match ($orientation) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => $image,
            };
        }

        return $image;
    }

    private static function scale(\GdImage $source, int $maxWidth): \GdImage
    {
        $width = imagesx($source);

        if ($width <= $maxWidth) {
            return $source;
        }

        $height = (int) round(imagesy($source) * $maxWidth / $width);
        $target = imagecreatetruecolor($maxWidth, $height);
        imagealphablending($target, false);
        imagesavealpha($target, true);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $maxWidth, $height, $width, imagesy($source));

        return $target;
    }
}
