<?php

namespace App\Support;

use BackedEnum;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\HtmlString;
use UnitEnum;

class EnumOptionBadge
{
    /**
     * @param  class-string<UnitEnum>  $enum
     * @return array<int|string, string>
     */
    public static function options(string $enum): array
    {
        if (! enum_exists($enum)) {
            return [];
        }

        return collect($enum::cases())
            ->mapWithKeys(fn (UnitEnum $case): array => [
                self::value($case) => (string) self::option($case),
            ])
            ->all();
    }

    public static function option(UnitEnum $case): HtmlString
    {
        $label = $case instanceof HasLabel
            ? ($case->getLabel() ?? $case->name)
            : $case->name;

        $icon = $case instanceof HasIcon ? $case->getIcon() : null;
        $colorName = self::colorName($case instanceof HasColor ? $case->getColor() : null);
        $background = self::colorHex($colorName);
        $glyphColor = self::isDarkBackground($background) ? '#ffffff' : '#0f172a';

        $svg = filled($icon)
            ? svg($icon, '', [
                'style' => "color: {$glyphColor}; width: 0.95rem; height: 0.95rem;",
            ])->toHtml()
            : '';

        return new HtmlString(
            '<span title="'.e($label).'" data-enum-color="'.e($colorName).'" data-icon="'.e($icon ?? '').'" '
            .'style="display: inline-flex; align-items: center; gap: 0.5rem;">'
            .'<span style="display: inline-flex; align-items: center; justify-content: center; '
            .'width: 1.5rem; height: 1.5rem; border-radius: 0.425rem; flex-shrink: 0; '
            ."background-color: {$background};\">"
            .$svg
            .'</span>'
            .'<span>'.e($label).'</span>'
            .'</span>'
        );
    }

    private static function value(UnitEnum $case): int|string
    {
        return $case instanceof BackedEnum ? $case->value : $case->name;
    }

    private static function colorName(mixed $color): string
    {
        if (is_array($color)) {
            $color = collect($color)->first(fn (mixed $value): bool => is_scalar($value));
        }

        return filled($color) && is_scalar($color) ? (string) $color : 'gray';
    }

    private static function colorHex(string $color): string
    {
        return match ($color) {
            'success' => '#16a34a',
            'warning' => '#f59e0b',
            'danger' => '#dc2626',
            'info' => '#0ea5e9',
            'teal' => '#0d9488',
            'orange' => '#f46b12',
            'violet' => '#7c3aed',
            default => '#64748b',
        };
    }

    private static function isDarkBackground(string $color): bool
    {
        $hex = ltrim($color, '#');

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return true;
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

        return $luminance < 0.55;
    }
}
