<?php

namespace App\Support\Financeiro;

final class MoneyAmount
{
    public static function toCents(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_int($value)) {
            return abs($value);
        }

        if (is_float($value)) {
            return abs((int) round($value));
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $value = str_replace(['R$', ' '], '', $value);

        if (str_contains($value, ',')) {
            $decimal = str_replace(',', '.', str_replace('.', '', $value));

            return abs((int) round(((float) $decimal) * 100));
        }

        if (preg_match('/^-?\d+\.\d{1,2}$/', $value) === 1) {
            return abs((int) round(((float) $value) * 100));
        }

        return abs((int) preg_replace('/[^\d]/', '', $value));
    }
}
