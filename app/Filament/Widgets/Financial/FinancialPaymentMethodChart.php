<?php

namespace App\Filament\Widgets\Financial;

use App\Filament\Widgets\Financial\Concerns\UsesFinancialDashboardData;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class FinancialPaymentMethodChart extends ApexChartWidget
{
    use UsesFinancialDashboardData;

    protected static ?string $chartId = 'financialPaymentMethodChart';

    protected static ?int $sort = 4;

    protected function getHeading(): ?string
    {
        return 'Saldo por forma de pagamento';
    }

    protected function getOptions(): array
    {
        $data = $this->emptyChartData($this->financialData()->paymentMethodBalances());

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'labels' => array_keys($data),
            'series' => array_values($data),
            'legend' => [
                'position' => 'bottom',
                'horizontalAlign' => 'center',
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'colors' => [
                '#14b8a6',
                '#22c55e',
                '#f97316',
                '#8b5cf6',
                '#0ea5e9',
                '#f43f5e',
                '#eab308',
                '#64748b',
            ],
            'stroke' => [
                'width' => 2,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return $this->currencyPieJsOptions();
    }
}
