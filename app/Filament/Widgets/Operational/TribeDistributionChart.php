<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use App\Support\Tribes\TribeColor;
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
        $operationalData = $this->operationalData();
        $data = $this->chartData($operationalData->tribes());
        $labels = array_keys($data);
        $colors = $operationalData->tribeColors();

        return [
            'chart' => [
                'type' => 'pie',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'labels' => $labels,
            'series' => array_values($data),
            'legend' => [
                'position' => 'bottom',
                'horizontalAlign' => 'center',
            ],
            'dataLabels' => [
                'enabled' => true,
            ],
            'colors' => array_map(fn (string $tribe): string => $colors[$tribe] ?? TribeColor::resolve(null, $tribe), $labels),
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
