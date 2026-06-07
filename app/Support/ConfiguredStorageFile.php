<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ConfiguredStorageFile
{
    public static function publicUrl(mixed $path): ?string
    {
        if (is_array($path)) {
            $path = reset($path);
        }

        if (blank($path) || ! is_string($path)) {
            return null;
        }

        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')
            ? $path
            : Storage::disk('public')->url($path);
    }
}
