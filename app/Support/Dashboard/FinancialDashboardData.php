<?php

namespace App\Support\Dashboard;

use App\Enums\FormaPagamento;
use App\Enums\TipoLacamento;
use App\Models\Lancamento;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinancialDashboardData
{
    public function forFilters(array $filters): FinancialDashboardDataSet
    {
        return new FinancialDashboardDataSet(
            baseQuery: $this->baseQuery($filters),
            categoryIds: FinancialDashboardFilters::categoryIds($filters),
        );
    }

    public function queryForFilters(array $filters): Builder
    {
        $categoryIds = FinancialDashboardFilters::categoryIds($filters);

        return $this->baseQuery($filters)
            ->when(
                $categoryIds !== [],
                fn (Builder $query): Builder => $query->whereHas(
                    'items',
                    fn (Builder $query): Builder => $query->whereIn('categoria_lancamento_id', $categoryIds),
                ),
            )
            ->with('items.categoria');
    }

    protected function baseQuery(array $filters): Builder
    {
        $startDate = FinancialDashboardFilters::startDate($filters);
        $endDate = FinancialDashboardFilters::endDate($filters);
        $statuses = FinancialDashboardFilters::statusValues($filters);
        $types = FinancialDashboardFilters::typeValues($filters);
        $paymentMethods = FinancialDashboardFilters::paymentMethodValues($filters);

        return Lancamento::query()
            ->when($startDate !== null, fn (Builder $query): Builder => $query->where('lancamentos.data', '>=', $startDate))
            ->when($endDate !== null, fn (Builder $query): Builder => $query->where('lancamentos.data', '<=', $endDate))
            ->when($statuses !== [], fn (Builder $query): Builder => $query->whereIn('lancamentos.status', $statuses))
            ->when($types !== [], fn (Builder $query): Builder => $query->whereIn('lancamentos.tipo', $types))
            ->when($paymentMethods !== [], fn (Builder $query): Builder => $query->whereIn('lancamentos.forma_pagamento', $paymentMethods));
    }
}

class FinancialDashboardDataSet
{
    public function __construct(
        private readonly Builder $baseQuery,
        private readonly array $categoryIds = [],
    ) {}

    public function summary(): array
    {
        $query = $this->query();

        if ($this->categoryIds === []) {
            $rows = $query
                ->select('lancamentos.tipo')
                ->selectRaw('COUNT(*) as entries, COALESCE(SUM(ABS(lancamentos.valor)), 0) as amount')
                ->groupBy('lancamentos.tipo')
                ->toBase()
                ->get();
        } else {
            $rows = $this->joinSelectedCategoryItems($query)
                ->select('lancamentos.tipo')
                ->selectRaw('COUNT(DISTINCT lancamentos.id) as entries, COALESCE(SUM(ABS(dashboard_items.valor)), 0) as amount')
                ->groupBy('lancamentos.tipo')
                ->toBase()
                ->get();
        }

        $totals = $this->totalsByType($rows);
        $revenue = $totals[TipoLacamento::Receita->value] ?? 0;
        $donations = $totals[TipoLacamento::Doacao->value] ?? 0;
        $expenses = $totals[TipoLacamento::Despesa->value] ?? 0;

        return [
            'entries' => $rows->sum(fn (object $row): int => (int) $row->entries),
            'revenue' => $revenue,
            'donations' => $donations,
            'expenses' => $expenses,
            'balance' => $revenue + $donations - $expenses,
        ];
    }

    public function dailyFlow(): array
    {
        $query = $this->query();

        if ($this->categoryIds === []) {
            $rows = $query
                ->select('lancamentos.tipo')
                ->selectRaw('DATE(lancamentos.data) as flow_date, COALESCE(SUM(ABS(lancamentos.valor)), 0) as amount')
                ->groupByRaw('DATE(lancamentos.data), lancamentos.tipo')
                ->orderBy('flow_date')
                ->toBase()
                ->get();
        } else {
            $rows = $this->joinSelectedCategoryItems($query)
                ->select('lancamentos.tipo')
                ->selectRaw('DATE(lancamentos.data) as flow_date, COALESCE(SUM(ABS(dashboard_items.valor)), 0) as amount')
                ->groupByRaw('DATE(lancamentos.data), lancamentos.tipo')
                ->orderBy('flow_date')
                ->toBase()
                ->get();
        }

        $flowByDate = [];

        foreach ($rows as $row) {
            $date = (string) $row->flow_date;

            $flowByDate[$date] ??= [
                'revenue' => 0,
                'donations' => 0,
                'expenses' => 0,
                'balance' => 0,
            ];

            $amount = (int) $row->amount;

            match ((int) $row->tipo) {
                TipoLacamento::Receita->value => $flowByDate[$date]['revenue'] += $amount,
                TipoLacamento::Doacao->value => $flowByDate[$date]['donations'] += $amount,
                TipoLacamento::Despesa->value => $flowByDate[$date]['expenses'] += $amount,
                default => null,
            };

            $flowByDate[$date]['balance'] = $flowByDate[$date]['revenue']
                + $flowByDate[$date]['donations']
                - $flowByDate[$date]['expenses'];
        }

        return collect($flowByDate)
            ->mapWithKeys(fn (array $dailyFlow, string $date): array => [
                Carbon::parse($date)->format('d/m') => $dailyFlow,
            ])
            ->all();
    }

    public function categoryTotals(int $limit = 10): array
    {
        return $this->query()
            ->join('lancamento_items as dashboard_items', 'dashboard_items.lancamento_id', '=', 'lancamentos.id')
            ->leftJoin('categorias_lancamento as dashboard_categories', 'dashboard_categories.id', '=', 'dashboard_items.categoria_lancamento_id')
            ->when(
                $this->categoryIds !== [],
                fn (Builder $query): Builder => $query->whereIn('dashboard_items.categoria_lancamento_id', $this->categoryIds),
            )
            ->selectRaw("COALESCE(dashboard_categories.nome, 'Sem categoria') as category_name")
            ->selectRaw('COALESCE(SUM(ABS(dashboard_items.valor)), 0) as amount')
            ->groupByRaw("COALESCE(dashboard_categories.nome, 'Sem categoria')")
            ->orderByDesc('amount')
            ->limit($limit)
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->category_name => (int) $row->amount])
            ->all();
    }

    public function paymentMethodBalances(): array
    {
        $query = $this->query();

        if ($this->categoryIds === []) {
            $rows = $query
                ->select(['lancamentos.forma_pagamento', 'lancamentos.tipo'])
                ->selectRaw('COALESCE(SUM(ABS(lancamentos.valor)), 0) as amount')
                ->selectRaw('MAX(lancamentos.data) as latest_date, MAX(lancamentos.id) as latest_id')
                ->groupBy('lancamentos.forma_pagamento', 'lancamentos.tipo')
                ->orderByDesc('latest_date')
                ->orderByDesc('latest_id')
                ->toBase()
                ->get();
        } else {
            $rows = $this->joinSelectedCategoryItems($query)
                ->select(['lancamentos.forma_pagamento', 'lancamentos.tipo'])
                ->selectRaw('COALESCE(SUM(ABS(dashboard_items.valor)), 0) as amount')
                ->selectRaw('MAX(lancamentos.data) as latest_date, MAX(lancamentos.id) as latest_id')
                ->groupBy('lancamentos.forma_pagamento', 'lancamentos.tipo')
                ->orderByDesc('latest_date')
                ->orderByDesc('latest_id')
                ->toBase()
                ->get();
        }

        $balances = collect();

        foreach ($rows as $row) {
            $label = $this->paymentMethodLabel($row->forma_pagamento);
            $balance = (int) $balances->get($label, 0);
            $amount = (int) $row->amount;

            $balance += match ((int) $row->tipo) {
                TipoLacamento::Receita->value, TipoLacamento::Doacao->value => $amount,
                TipoLacamento::Despesa->value => -$amount,
                default => 0,
            };

            $balances->put($label, $balance);
        }

        return $balances
            ->filter(fn (int $balance): bool => $balance !== 0)
            ->sortDesc()
            ->all();
    }

    public function recentEntries(int $limit = 8): Collection
    {
        return $this->query()
            ->when(
                $this->categoryIds !== [],
                fn (Builder $query): Builder => $query->whereHas(
                    'items',
                    fn (Builder $query): Builder => $query->whereIn('categoria_lancamento_id', $this->categoryIds),
                ),
            )
            ->with('items.categoria')
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    private function query(): Builder
    {
        return clone $this->baseQuery;
    }

    private function joinSelectedCategoryItems(Builder $query): Builder
    {
        return $query
            ->join('lancamento_items as dashboard_items', 'dashboard_items.lancamento_id', '=', 'lancamentos.id')
            ->whereIn('dashboard_items.categoria_lancamento_id', $this->categoryIds);
    }

    private function totalsByType(Collection $rows): array
    {
        return $rows
            ->mapWithKeys(fn (object $row): array => [(int) $row->tipo => (int) $row->amount])
            ->all();
    }

    private function paymentMethodLabel(mixed $paymentMethod): string
    {
        return FormaPagamento::tryFrom((int) $paymentMethod)?->getLabel() ?? 'Não informado';
    }
}
