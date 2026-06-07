<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\Column;
use Illuminate\Database\Eloquent\Model;

class ColoredIconColumn extends Column
{
    protected string $view = 'filament.tables.columns.colored-icon-column';

    protected string $fallbackColor = '#94a3b8';

    protected string $fallbackIcon = 'heroicon-o-tag';

    public function getBackgroundColor(Model $record): string
    {
        return '#'.$this->normalizeHex(data_get($record, 'cor'));
    }

    public function getIconColor(Model $record): string
    {
        return $this->isDarkBackground(data_get($record, 'cor')) ? '#ffffff' : '#0f172a';
    }

    public function getIconName(Model $record): string
    {
        return data_get($record, 'icone') ?: $this->fallbackIcon;
    }

    private function normalizeHex(?string $color): string
    {
        $hex = ltrim((string) ($color ?? $this->fallbackColor), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return ltrim($this->fallbackColor, '#');
        }

        return $hex;
    }

    private function isDarkBackground(?string $color): bool
    {
        $hex = $this->normalizeHex($color);

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

        return $luminance < 0.55;
    }
}
