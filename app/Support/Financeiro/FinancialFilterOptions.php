<?php

namespace App\Support\Financeiro;

use App\Enums\StatusLacamento;
use App\Models\CategoriaLancamento;
use App\Support\EnumOptionBadge;
use App\Support\IconBadge;

final class FinancialFilterOptions
{
    /**
     * @return array<int, string>
     */
    public static function categories(): array
    {
        return CategoriaLancamento::query()
            ->orderBy('nome')
            ->get(['id', 'nome', 'cor', 'icone'])
            ->mapWithKeys(fn (CategoriaLancamento $category): array => [
                $category->getKey() => (string) IconBadge::tile(
                    $category,
                    $category->nome,
                    fallbackIcon: 'heroicon-o-tag',
                ),
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function paymentStatuses(): array
    {
        return EnumOptionBadge::options(StatusLacamento::class);
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, string>
     */
    public static function categoryIndicators(array $state): array
    {
        $categoryIds = self::selectedIntegerValues($state);

        if ($categoryIds === []) {
            return [];
        }

        return CategoriaLancamento::query()
            ->whereKey($categoryIds)
            ->orderBy('nome')
            ->pluck('nome')
            ->map(fn (string $name): string => 'Categoria: '.$name)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, string>
     */
    public static function paymentStatusIndicators(array $state): array
    {
        return collect(self::selectedIntegerValues($state))
            ->map(fn (int $value): string => 'Status do pagamento: '.(StatusLacamento::tryFrom($value)?->getLabel() ?? (string) $value))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $state
     * @return array<int, int>
     */
    public static function selectedIntegerValues(array $state): array
    {
        return collect($state['values'] ?? [$state['value'] ?? null])
            ->filter(fn (mixed $value): bool => filled($value))
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values()
            ->all();
    }
}
