<?php

namespace App\Filament\Widgets\Financial;

use App\Filament\Widgets\Financial\Concerns\UsesFinancialDashboardData;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class FinancialDailyFlowChart extends ApexChartWidget
{
    use UsesFinancialDashboardData;

    protected static ?string $chartId = 'financialDailyFlowChart';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return 'Fluxo financeiro por dia';
    }

    protected function getOptions(): array
    {
        $data = $this->emptyFlow($this->financialData()->dailyFlow());
        $axisValues = collect($data)
            ->flatMap(fn (array $flow): array => array_values($flow))
            ->all();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 330,
                'toolbar' => ['show' => false],
                'stacked' => false,
            ],
            'series' => [
                [
                    'name' => 'Receitas',
                    'data' => array_column($data, 'revenue'),
                ],
                [
                    'name' => 'Doações',
                    'data' => array_column($data, 'donations'),
                ],
                [
                    'name' => 'Despesas',
                    'data' => array_column($data, 'expenses'),
                ],
                [
                    'name' => 'Saldo',
                    'type' => 'line',
                    'data' => array_column($data, 'balance'),
                ],
            ],
            'xaxis' => [
                'categories' => array_keys($data),
            ],
            'yaxis' => [
                'min' => min(0, ...$axisValues),
                'decimalsInFloat' => 0,
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => [0, 0, 0, 3],
            ],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 4,
                    'columnWidth' => '55%',
                ],
            ],
            'dataLabels' => ['enabled' => false],
            'colors' => ['#22c55e', '#0ea5e9', '#f43f5e', '#f97316'],
            'grid' => [
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 4,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return $this->currencyYAxisJsOptions();
    }

    private function emptyFlow(array $data): array
    {
        return $data === []
            ? ['Sem dados' => ['revenue' => 0, 'donations' => 0, 'expenses' => 0, 'balance' => 0]]
            : $data;
    }
}
