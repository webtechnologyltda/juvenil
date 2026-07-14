<?php

namespace App\Support\Reports;

use GdImage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PrintableReportImage
{
    private const int MAX_DIMENSION = 320;

    private const int JPEG_QUALITY = 82;

    public function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        $disk = Storage::disk('public');
        $originalUrl = $disk->url($path);

        if (! function_exists('imagecreatefromstring') || ! $disk->exists($path)) {
            return $originalUrl;
        }

        $thumbnailPath = $this->thumbnailPath($path);

        try {
            if (! $this->isFresh($disk, $path, $thumbnailPath)) {
                $thumbnail = $this->thumbnail($disk->get($path));

                if ($thumbnail === null || ! $disk->put($thumbnailPath, $thumbnail, ['visibility' => 'public'])) {
                    return $originalUrl;
                }
            }
        } catch (Throwable) {
            return $originalUrl;
        }

        return $disk->url($thumbnailPath);
    }

    public function thumbnailPath(string $path): string
    {
        return 'report-thumbnails/'.sha1($path).'.jpg';
    }

    private function isFresh(FilesystemAdapter $disk, string $originalPath, string $thumbnailPath): bool
    {
        return $disk->exists($thumbnailPath)
            && $disk->size($thumbnailPath) > 0
            && $disk->lastModified($thumbnailPath) >= $disk->lastModified($originalPath);
    }

    private function thumbnail(string $contents): ?string
    {
        $source = @imagecreatefromstring($contents);

        if (! $source instanceof GdImage) {
            return null;
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, self::MAX_DIMENSION / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $target instanceof GdImage) {
            unset($source);

            return null;
        }

        $background = imagecolorallocate($target, 255, 255, 255);
        imagefill($target, 0, 0, $background);
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        ob_start();
        $encoded = @imagejpeg($target, null, self::JPEG_QUALITY);
        $thumbnail = ob_get_clean();

        unset($target, $source);

        return $encoded && is_string($thumbnail) ? $thumbnail : null;
    }
}
