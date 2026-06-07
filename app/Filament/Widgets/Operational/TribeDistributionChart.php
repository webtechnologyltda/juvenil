<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class TribeDistributionChart extends ApexChartWidget
{
    use UsesOperationalDashboardData;

    protected static ?string $chartId = 'tribeDistributionChart';

    protected static ?int $sort = 5;

    protected function getHeading(): ?string
    {
        return 'Distribuição por tribo';
    }

    protected function getSubheading(): ?string
    {
        return 'Inclui campistas ainda sem tribo definida pela equipe.';
    }

    protected function getOptions(): array
    {
        $data = $this->chartData($this->operationalData()->tribes());

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
                '#7c3aed',
                '#0891b2',
                '#f97316',
                '#16a34a',
                '#dc2626',
                '#d946ef',
                '#64748b',
                '#eab308',
            ],
            'stroke' => [
                'width' => 2,
            ],
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            dataLabels: {
                formatter: function (value, options) {
                    return options.w.config.series[options.seriesIndex]
                }
            },
            tooltip: {
                y: {
                    formatter: function (value) {
                        return Number(value).toFixed(0) + ' campistas'
                    }
                }
            }
        }
        JS);
    }
}
