<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class IconBadge
{
    public static function tile(?Model $record, string $label, string $fallbackIcon = 'heroicon-o-tag'): HtmlString
    {
        if ($record === null) {
            return new HtmlString(e($label));
        }

        $icon = self::icon($record, $fallbackIcon);
        $background = self::color($record);
        $glyphColor = self::isDarkBackground($background) ? '#ffffff' : '#0f172a';

        $svg = svg($icon, '', [
            'style' => "color: {$glyphColor}; width: 1rem; height: 1rem;",
        ])->toHtml();

        return new HtmlString(
            '<span title="'.e($label).'" data-icon="'.e($icon).'" style="display: inline-flex; align-items: center; gap: 0.625rem;">'
            .'<span style="display: inline-flex; align-items: center; justify-content: center; '
            .'width: 2rem; height: 2rem; border-radius: 0.5rem; flex-shrink: 0; '
            ."background-color: {$background};\">"
            .$svg
            .'</span>'
            .'<span>'.e($label).'</span>'
            .'</span>'
        );
    }

    public static function tileIcon(?Model $record, string $label, string $fallbackIcon = 'heroicon-o-tag'): HtmlString
    {
        if ($record === null) {
            return new HtmlString(e($label));
        }

        $icon = self::icon($record, $fallbackIcon);
        $background = self::color($record);
        $glyphColor = self::isDarkBackground($background) ? '#ffffff' : '#0f172a';

        $svg = svg($icon, '', [
            'style' => "color: {$glyphColor}; width: 1.1rem; height: 1.1rem;",
        ])->toHtml();

        return new HtmlString(
            '<span aria-label="'.e($label).'" title="'.e($label).'" data-icon="'.e($icon).'" '
            .'style="display: inline-flex; align-items: center; justify-content: center; '
            .'width: 2rem; height: 2rem; border-radius: 0.5rem; flex-shrink: 0; '
            ."background-color: {$background};\">"
            .$svg
            .'</span>'
        );
    }

    private static function icon(Model $record, string $fallbackIcon): string
    {
        return filled($record->getAttribute('icone'))
            ? (string) $record->getAttribute('icone')
            : (filled($record->getAttribute('icon')) ? (string) $record->getAttribute('icon') : $fallbackIcon);
    }

    private static function color(Model $record): string
    {
        return filled($record->getAttribute('cor'))
            ? (string) $record->getAttribute('cor')
            : (filled($record->getAttribute('color')) ? (string) $record->getAttribute('color') : '#94a3b8');
    }

    private static function isDarkBackground(string $color): bool
    {
        $hex = ltrim($color, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

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
