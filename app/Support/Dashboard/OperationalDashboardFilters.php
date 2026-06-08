<?php

namespace App\Support\Dashboard;

use App\Enums\StatusInscricao;

class OperationalDashboardFilters
{
    public static function statusValues(array $filters): array
    {
        return self::integerList($filters['status'] ?? []);
    }

    public static function tribeIds(array $filters): array
    {
        return self::integerList($filters['tribo_id'] ?? []);
    }

    public static function validStatuses(array $filters): array
    {
        $statuses = self::statusValues($filters);

        return $statuses === []
            ? [StatusInscricao::Pendente->value, StatusInscricao::Pago->value]
            : $statuses;
    }

    public static function formFilter(array $filters, string $key): mixed
    {
        if (! array_key_exists($key, $filters) || $filters[$key] === null || $filters[$key] === '') {
            return null;
        }

        $value = $filters[$key];

        if (is_string($value)) {
            $value = trim($value);
        }

        if ($value === '') {
            return null;
        }

        return is_numeric($value) && (string) (int) $value === (string) $value
            ? (int) $value
            : $value;
    }

    public static function communityValues(array $filters): array
    {
        if (! array_key_exists('comunidade', $filters) || $filters['comunidade'] === null || $filters['comunidade'] === '') {
            return [];
        }

        $values = is_array($filters['comunidade'])
            ? $filters['comunidade']
            : [$filters['comunidade']];

        return collect($values)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(function (mixed $value): mixed {
                if (is_string($value)) {
                    $value = trim($value);
                }

                return is_numeric($value) && (string) (int) $value === (string) $value
                    ? (int) $value
                    : $value;
            })
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->values()
            ->all();
    }

    public static function communityText(array $filters): ?string
    {
        $value = $filters['comunidade_texto'] ?? null;

        if ($value === null || $value === '') {
            $value = self::legacyOtherParishCommunityText($filters);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    public static function presence(array $filters): ?bool
    {
        if (! array_key_exists('presenca', $filters) || $filters['presenca'] === null || $filters['presenca'] === '') {
            return null;
        }

        return (bool) (int) $filters['presenca'];
    }

    private static function integerList(mixed $values): array
    {
        $values = is_array($values) ? $values : [$values];

        return collect($values)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => $value instanceof StatusInscricao ? $value->value : (int) $value)
            ->values()
            ->all();
    }

    private static function legacyOtherParishCommunityText(array $filters): ?string
    {
        if (self::formFilter($filters, 'paroquia') !== 2) {
            return null;
        }

        $value = $filters['comunidade'] ?? null;

        if (! is_string($value) || is_numeric($value)) {
            return null;
        }

        return $value;
    }
}
