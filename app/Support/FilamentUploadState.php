<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

final class FilamentUploadState
{
    public static function storedPath(mixed $state, string $directory, string $disk = 'public'): ?string
    {
        $file = self::firstValue($state);

        if (is_string($file)) {
            return $file;
        }

        if ($file instanceof UploadedFile || (is_object($file) && method_exists($file, 'store'))) {
            $path = $file->store($directory, $disk);

            return is_string($path) ? $path : null;
        }

        return null;
    }

    private static function firstValue(mixed $state): mixed
    {
        if (! is_array($state)) {
            return $state;
        }

        if ($state === []) {
            return null;
        }

        return $state[array_key_first($state)];
    }
}
