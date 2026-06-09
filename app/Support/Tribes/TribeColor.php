<?php

namespace App\Support\Tribes;

use App\Models\Tribo;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class TribeColor
{
    public const FALLBACK = '#94a3b8';

    public static function forTribe(?Tribo $tribe, string $fallback = self::FALLBACK): string
    {
        return self::resolve($tribe?->cor_hex, $tribe?->cor, $fallback);
    }

    public static function resolve(?string $registeredColor, ?string $label = null, string $fallback = self::FALLBACK): string
    {
        return self::normalizeHex($registeredColor)
            ?? self::normalizeHex($label)
            ?? self::fromName($label)
            ?? $fallback;
    }

    public static function fromName(?string $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        return match (Str::lower(Str::ascii(trim($name)))) {
            'azul' => '#2563eb',
            'vermelha', 'vermelho' => '#dc2626',
            'verde' => '#16a34a',
            'amarela', 'amarelo' => '#eab308',
            'roxa', 'roxo' => '#7c3aed',
            'laranja' => '#f97316',
            'rosa' => '#ec4899',
            'branca', 'branco' => '#f8fafc',
            'preta', 'preto' => '#111827',
            'cinza' => '#64748b',
            default => null,
        };
    }

    public static function badge(?Tribo $tribe, string $emptyLabel = 'Sem tribo'): HtmlString
    {
        $label = $tribe?->cor ?: $emptyLabel;
        $color = self::forTribe($tribe);

        return new HtmlString(sprintf(
            '<span style="display:inline-flex;align-items:center;gap:.4rem;"><span style="width:.72rem;height:.72rem;border-radius:999px;border:1px solid rgba(148,163,184,.45);background:%s;"></span><span>%s</span></span>',
            e($color),
            e($label),
        ));
    }

    public static function contrastText(?string $color): string
    {
        return self::isLight($color) ? '#03181c' : '#ffffff';
    }

    public static function isLight(?string $color): bool
    {
        $hex = self::normalizeHex($color);

        if ($hex === null) {
            return false;
        }

        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $channels = [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];

        $linear = array_map(
            fn (float $channel): float => $channel <= 0.03928
                ? $channel / 12.92
                : (($channel + 0.055) / 1.055) ** 2.4,
            $channels,
        );

        $luminance = (0.2126 * $linear[0]) + (0.7152 * $linear[1]) + (0.0722 * $linear[2]);

        return $luminance >= 0.45;
    }

    private static function normalizeHex(?string $color): ?string
    {
        if (blank($color)) {
            return null;
        }

        $normalized = Str::lower(Str::ascii(trim($color)));

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $normalized) === 1
            ? $normalized
            : null;
    }
}
