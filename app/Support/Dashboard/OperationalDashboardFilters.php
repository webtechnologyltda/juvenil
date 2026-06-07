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
}
