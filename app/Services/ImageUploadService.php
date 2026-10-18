<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Preset dimensions, quality, and storage folders.
     */
    public const PRESETS = [
        'candidate' => [
            'maxWidth'  => 800,
            'maxHeight' => 800,
            'quality'   => 82,
            'folder'    => 'foto-kandidat',
        ],
        'event' => [
            'maxWidth'  => 1200,
            'maxHeight' => 800,
            'quality'   => 82,
            'folder'    => 'foto-kegiatan',
        ],
        'student' => [
            'maxWidth'  => 400,
            'maxHeight' => 400,
            'quality'   => 82,
            'folder'    => 'foto-mahasiswa',
        ],
    ];

    /**
     * Store and optimize candidate photo.
     */
    public static function storeCandidate(UploadedFile $file): string
    {
        return self::store($file, self::PRESETS['candidate']);
    }

    /**
     * Store and optimize event photo.
     */
    public static function storeEvent(UploadedFile $file): string
    {
        return self::store($file, self::PRESETS['event']);
    }

    /**
     * Store and optimize student avatar.
     */
    public static function storeStudent(UploadedFile $file): string
    {
        return self::store($file, self::PRESETS['student']);
    }

    /**
     * Store uploaded file: resize to preset maximums, encode as WebP, and preserve 40-char random hash.
     * If GD cannot decode the stream (e.g. dummy zero-byte test files), fallback to raw file storage.
     *
     * @param UploadedFile $file
     * @param array{maxWidth: int, maxHeight: int, quality: int, folder: string} $config
     * @return string Relative path stored in database (e.g. "foto-kandidat/XyZ...40chars.webp")
     */
    public static function store(UploadedFile $file, array $config): string
    {
        $folder    = $config['folder'];
        $maxWidth  = $config['maxWidth'];
        $maxHeight = $config['maxHeight'];
        $quality   = $config['quality'];

        $disk = Storage::disk('public');
        $folderPath = $disk->path($folder);
        if (!file_exists($folderPath)) {
            @mkdir($folderPath, 0755, true);
        }

        $realPath = $file->getRealPath() ?: $file->path();

        if ($realPath && file_exists($realPath) && filesize($realPath) > 0) {
            $optimizedWebp = self::processImageToWebp($realPath, $maxWidth, $maxHeight, $quality);
            if ($optimizedWebp !== null) {
                $filename = Str::random(40) . '.webp';
                $relativePath = $folder . '/' . $filename;
                $disk->put($relativePath, $optimizedWebp);
                return $relativePath;
            }
        }

        // Graceful fallback for non-decodable streams (e.g. UploadedFile::fake()->create(...))
        $filename = $file->hashName();
        $disk->putFileAs($folder, $file, $filename);
        return $folder . '/' . $filename;
    }

    /**
     * Process an image file: resize proportionally without upscaling,
     * preserve transparency, adjust EXIF orientation, and encode as WebP.
     *
     * @param string $filePath Full path to image file
     * @param int $maxWidth Max allowed width
     * @param int $maxHeight Max allowed height
     * @param int $quality WebP quality factor (0-100)
     * @return string|null WebP binary string, or null on failure
     */
    public static function processImageToWebp(string $filePath, int $maxWidth, int $maxHeight, int $quality = 82): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath) || filesize($filePath) === 0) {
            return null;
        }

        // 1. Primary engine: Pure PHP GD implementation with libwebp (active on production Hostinger)
        if (extension_loaded('gd') && function_exists('imagewebp')) {
            $gdResult = self::processWithGd($filePath, $maxWidth, $maxHeight, $quality);
            if ($gdResult !== null) {
                return $gdResult;
            }
        }

        // 2. Fallback engine: Python Pillow (for local CLI environments where PHP GD extension is disabled in php.ini)
        return self::processWithPillow($filePath, $maxWidth, $maxHeight, $quality);
    }

    /**
     * Pure PHP GD implementation.
     */
    protected static function processWithGd(string $filePath, int $maxWidth, int $maxHeight, int $quality = 82): ?string
    {
        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return null;
        }

        $mime = $imageInfo['mime'] ?? '';
        $source = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($filePath),
            'image/png'               => @imagecreatefrompng($filePath),
            'image/webp'              => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($filePath) : null,
            default                   => null,
        };

        if (!$source) {
            $raw = @file_get_contents($filePath);
            if ($raw) {
                $source = @imagecreatefromstring($raw);
            }
        }

        if (!$source) {
            return null;
        }

        // Handle camera rotation from EXIF metadata for JPEG
        if (($mime === 'image/jpeg' || $mime === 'image/jpg') && function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($filePath);
                if (!empty($exif['Orientation'])) {
                    $angle = match ((int) $exif['Orientation']) {
                        3 => 180,
                        6 => -90,
                        8 => 90,
                        default => 0,
                    };

                    if ($angle !== 0) {
                        $rotated = @imagerotate($source, $angle, 0);
                        if ($rotated !== false) {
                            imagedestroy($source);
                            $source = $rotated;
                        }
                    }
                }
            } catch (\Throwable) {
                // Ignore EXIF parsing failures
            }
        }

        $origWidth  = imagesx($source);
        $origHeight = imagesy($source);

        if ($origWidth <= 0 || $origHeight <= 0) {
            imagedestroy($source);
            return null;
        }

        // Calculate proportional downscale without upscaling
        if ($origWidth <= $maxWidth && $origHeight <= $maxHeight) {
            $targetWidth  = $origWidth;
            $targetHeight = $origHeight;
        } else {
            $ratio        = min($maxWidth / $origWidth, $maxHeight / $origHeight);
            $targetWidth  = max(1, (int) round($origWidth * $ratio));
            $targetHeight = max(1, (int) round($origHeight * $ratio));
        }

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$target) {
            imagedestroy($source);
            return null;
        }

        // Preserve alpha transparency for PNG/WebP
        imagealphablending($target, false);
        imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $origWidth,
            $origHeight
        );

        ob_start();
        $saved = imagewebp($target, null, $quality);
        $webpData = ob_get_clean();

        imagedestroy($target);
        imagedestroy($source);

        if (!$saved || empty($webpData)) {
            return null;
        }

        return $webpData;
    }

    /**
     * Fallback processor using Python Pillow when PHP GD is uncompiled or disabled in CLI php.ini.
     */
    protected static function processWithPillow(string $filePath, int $maxWidth, int $maxHeight, int $quality = 82): ?string
    {
        $normalizedPath = str_replace('\\', '/', $filePath);
        $escPath = json_encode($normalizedPath);

        $pyCode = "
import sys, os
from PIL import Image, ImageOps
try:
    img = Image.open({$escPath})
    try:
        img = ImageOps.exif_transpose(img)
    except Exception:
        pass
    orig_w, orig_h = img.size
    if orig_w > {$maxWidth} or orig_h > {$maxHeight}:
        ratio = min({$maxWidth} / orig_w, {$maxHeight} / orig_h)
        target_w = max(1, int(round(orig_w * ratio)))
        target_h = max(1, int(round(orig_h * ratio)))
        img = img.resize((target_w, target_h), Image.Resampling.LANCZOS)
    if img.mode not in ('RGB', 'RGBA'):
        img = img.convert('RGBA')
    out_path = {$escPath} + '.tmp.webp'
    img.save(out_path, 'WEBP', quality={$quality}, method=4)
    with open(out_path, 'rb') as f:
        sys.stdout.buffer.write(f.read())
    try:
        os.remove(out_path)
    except Exception:
        pass
except Exception:
    sys.exit(1)
";

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open('python -', $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            return null;
        }

        fwrite($pipes[0], $pyCode);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode === 0 && !empty($output)) {
            return $output;
        }

        return null;
    }
}
