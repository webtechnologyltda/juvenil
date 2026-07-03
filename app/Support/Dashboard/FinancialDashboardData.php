<?php

namespace App\Support\Dashboard;

use App\Enums\FormaPagamento;
use App\Enums\TipoLacamento;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinancialDashboardData
{
    public function forFilters(array $filters): FinancialDashboardDataSet
    {
        return new FinancialDashboardDataSet(
            records: $this->records($filters),
            categoryIds: FinancialDashboardFilters::categoryIds($filters),
        );
    }

    public function queryForFilters(array $filters): Builder
    {
        $startDate = FinancialDashboardFilters::startDate($filters);
        $endDate = FinancialDashboardFilters::endDate($filters);
        $statuses = FinancialDashboardFilters::statusValues($filters);
        $types = FinancialDashboardFilters::typeValues($filters);
        $categoryIds = FinancialDashboardFilters::categoryIds($filters);
        $paymentMethods = FinancialDashboardFilters::paymentMethodValues($filters);

        return Lancamento::query()
            ->with('items.categoria')
            ->when($startDate !== null, fn (Builder $query): Builder => $query->where('data', '>=', $startDate))
            ->when($endDate !== null, fn (Builder $query): Builder => $query->where('data', '<=', $endDate))
            ->when($statuses !== [], fn (Builder $query): Builder => $query->whereIn('status', $statuses))
            ->when($types !== [], fn (Builder $query): Builder => $query->whereIn('tipo', $types))
            ->when($categoryIds !== [], fn (Builder $query): Builder => $query->whereHas('items', fn (Builder $query): Builder => $query->whereIn('categoria_lancamento_id', $categoryIds)))
            ->when($paymentMethods !== [], fn (Builder $query): Builder => $query->whereIn('forma_pagamento', $paymentMethods));
    }

    protected function records(array $filters): Collection
    {
        return $this->queryForFilters($filters)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get();
    }
}

class FinancialDashboardDataSet
{
    public function __construct(
        private readonly Collection $records,
        private readonly array $categoryIds = [],
    ) {}

    public function summary(): array
    {
        $revenue = $this->totalFor(TipoLacamento::Receita);
        $donations = $this->totalFor(TipoLacamento::Doacao);
        $expenses = $this->totalFor(TipoLacamento::Despesa);

        return [
            'entries' => $this->records->count(),
            'revenue' => $revenue,
            'donations' => $donations,
            'expenses' => $expenses,
            'balance' => $revenue + $donations - $expenses,
        ];
    }

    public function dailyFlow(): array
    {
        return $this->records
            ->sortBy(fn (Lancamento $lancamento): string => $this->dateKey($lancamento))
            ->groupBy(fn (Lancamento $lancamento): string => $this->dateKey($lancamento))
            ->mapWithKeys(fn (Collection $records, string $date): array => [
                Carbon::parse($date)->format('d/m') => $this->flowFor($records),
            ])
            ->all();
    }

    public function categoryTotals(int $limit = 10): array
    {
        return $this->records
            ->flatMap(fn (Lancamento $lancamento): Collection => $this->categoryItems($lancamento))
            ->groupBy(fn (LancamentoItem $item): string => $item->categoria?->nome ?: 'Sem categoria')
            ->map(fn (Collection $items): int => $items->sum(fn (LancamentoItem $item): int => abs((int) $item->valor)))
            ->sortDesc()
            ->take($limit)
            ->all();
    }

    public function paymentMethodBalances(): array
    {
        return $this->records
            ->groupBy(fn (Lancamento $lancamento): string => $this->paymentMethodLabel($lancamento))
            ->map(fn (Collection $records): int => $this->flowFor($records)['balance'])
            ->filter(fn (int $balance): bool => $balance !== 0)
            ->sortDesc()
            ->all();
    }

    public function recentEntries(int $limit = 8): Collection
    {
        return $this->records->take($limit)->values();
    }

    private function flowFor(Collection $records): array
    {
        $revenue = $this->totalFor(TipoLacamento::Receita, $records);
        $donations = $this->totalFor(TipoLacamento::Doacao, $records);
        $expenses = $this->totalFor(TipoLacamento::Despesa, $records);

        return [
            'revenue' => $revenue,
            'donations' => $donations,
            'expenses' => $expenses,
            'balance' => $revenue + $donations - $expenses,
        ];
    }

    private function totalFor(TipoLacamento $type, ?Collection $records = null): int
    {
        return ($records ?? $this->records)
            ->filter(fn (Lancamento $lancamento): bool => $this->typeIs($lancamento, $type))
            ->sum(fn (Lancamento $lancamento): int => $this->amount($lancamento));
    }

    private function typeIs(Lancamento $lancamento, TipoLacamento $type): bool
    {
        return $lancamento->tipo instanceof TipoLacamento
            ? $lancamento->tipo === $type
            : (int) $lancamento->tipo === $type->value;
    }

    private function amount(Lancamento $lancamento): int
    {
        if ($this->categoryIds !== []) {
            return $this->categoryItems($lancamento)
                ->sum(fn (LancamentoItem $item): int => abs((int) $item->valor));
        }

        return abs((int) $lancamento->valor);
    }

    private function paymentMethodLabel(Lancamento $lancamento): string
    {
        if ($lancamento->forma_pagamento instanceof FormaPagamento) {
            return $lancamento->forma_pagamento->getLabel() ?? 'Não informado';
        }

        $paymentMethod = FormaPagamento::tryFrom((int) $lancamento->forma_pagamento);

        return $paymentMethod?->getLabel() ?? 'Não informado';
    }

    private function categoryItems(Lancamento $lancamento): Collection
    {
        $items = $lancamento->relationLoaded('items')
            ? $lancamento->items
            : $lancamento->items()->with('categoria')->get();

        if ($this->categoryIds === []) {
            return $items;
        }

        return $items
            ->filter(fn (LancamentoItem $item): bool => in_array((int) $item->categoria_lancamento_id, $this->categoryIds, true))
            ->values();
    }

    private function dateKey(Lancamento $lancamento): string
    {
        return Carbon::parse($lancamento->data)->format('Y-m-d');
    }
}
