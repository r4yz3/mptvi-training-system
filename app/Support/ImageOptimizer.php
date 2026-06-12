<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Stdlib (GD) image optimizer — resizes oversized images and re-compresses them
 * in their original format. Non-images (PDF, docx, …) are passed through
 * untouched, so callers can apply it to any upload safely.
 */
class ImageOptimizer
{
    /** Resize within a square bound + re-compress raw image bytes. Returns the smaller of {optimized, original}. */
    public static function bytes(string $bytes, int $maxDim = 1600, int $quality = 82): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $bytes;
        }

        $info = @getimagesizefromstring($bytes);
        if (! $info) {
            return $bytes; // not a raster image (e.g. PDF, SVG) — leave as-is
        }

        [$w, $h] = $info;
        $type = $info[2];
        $supported = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF];
        if (! in_array($type, $supported, true) || $w < 1 || $h < 1) {
            return $bytes;
        }

        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            return $bytes;
        }

        $scale = min(1, $maxDim / max($w, $h));
        if ($scale < 1) {
            $nw = max(1, (int) round($w * $scale));
            $nh = max(1, (int) round($h * $scale));
            $dst = imagecreatetruecolor($nw, $nh);

            if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
            }

            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        ob_start();
        switch ($type) {
            case IMAGETYPE_JPEG:
                imageinterlace($src, true); // progressive JPEG
                imagejpeg($src, null, $quality);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($src, null, $quality);
                break;
            case IMAGETYPE_GIF:
                imagegif($src);
                break;
            case IMAGETYPE_PNG:
            default:
                imagesavealpha($src, true);
                imagepng($src, null, 8); // zlib level 0-9
                break;
        }
        $out = ob_get_clean();
        imagedestroy($src);

        return ($out !== false && strlen($out) > 0 && strlen($out) < strlen($bytes)) ? $out : $bytes;
    }

    /** Optimize an uploaded file in place (call before ->store()). No-op for non-images. */
    public static function uploaded(UploadedFile $file, int $maxDim = 1600, int $quality = 82): void
    {
        $path = $file->getPathname();
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            return;
        }
        $opt = self::bytes($bytes, $maxDim, $quality);
        if ($opt !== $bytes) {
            @file_put_contents($path, $opt);
        }
    }

    /** Optimize a file already stored on a disk, overwriting only if it got smaller. */
    public static function stored(string $disk, string $path, int $maxDim = 1600, int $quality = 82): void
    {
        $d = Storage::disk($disk);
        if (! $d->exists($path)) {
            return;
        }
        $bytes = $d->get($path);
        $opt = self::bytes($bytes, $maxDim, $quality);
        if ($opt !== $bytes) {
            $d->put($path, $opt);
        }
    }

    /** Optimize a raw filesystem path in place (used for public logos). */
    public static function file(string $absolutePath, int $maxDim = 1600, int $quality = 82): void
    {
        $bytes = @file_get_contents($absolutePath);
        if ($bytes === false) {
            return;
        }
        $opt = self::bytes($bytes, $maxDim, $quality);
        if ($opt !== $bytes) {
            @file_put_contents($absolutePath, $opt);
        }
    }
}
