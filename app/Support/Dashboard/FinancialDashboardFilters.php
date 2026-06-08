<?php

namespace App\Support\Dashboard;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use BackedEnum;
use Carbon\CarbonImmutable;

class FinancialDashboardFilters
{
    public static function startDate(array $filters): ?CarbonImmutable
    {
        return self::date($filters['data_inicio'] ?? null)?->startOfDay();
    }

    public static function endDate(array $filters): ?CarbonImmutable
    {
        return self::date($filters['data_fim'] ?? null)?->endOfDay();
    }

    public static function statusValues(array $filters): array
    {
        $statuses = self::integerList($filters['status'] ?? []);

        return $statuses === []
            ? [StatusLacamento::Pago->value]
            : $statuses;
    }

    public static function typeValues(array $filters): array
    {
        return self::integerList($filters['tipo'] ?? [], TipoLacamento::class);
    }

    public static function categoryIds(array $filters): array
    {
        return self::integerList($filters['categoria_lancamento_id'] ?? []);
    }

    public static function paymentMethodValues(array $filters): array
    {
        return self::integerList($filters['forma_pagamento'] ?? [], FormaPagamento::class);
    }

    private static function date(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  class-string<BackedEnum>|null  $enum
     */
    private static function integerList(mixed $values, ?string $enum = null): array
    {
        $values = is_array($values) ? $values : [$values];

        return collect($values)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(function (mixed $value) use ($enum): ?int {
                if ($value instanceof BackedEnum) {
                    return (int) $value->value;
                }

                if ($enum !== null && enum_exists($enum)) {
                    $case = $enum::tryFrom(is_numeric($value) ? (int) $value : $value);

                    return $case instanceof BackedEnum ? (int) $case->value : null;
                }

                return is_numeric($value) ? (int) $value : null;
            })
            ->filter(fn (?int $value): bool => $value !== null)
            ->values()
            ->all();
    }
}
