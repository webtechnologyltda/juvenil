<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class SexDistributionChart extends ApexChartWidget
{
    use UsesOperationalDashboardData;

    protected static ?string $chartId = 'sexDistributionChart';

    protected static ?int $sort = 9;

    protected function getHeading(): ?string
    {
        return 'Acompanhamento por sexo';
    }

    protected function getSubheading(): ?string
    {
        return 'Totais por sexo informado pelos campistas.';
    }

    protected function getOptions(): array
    {
        $data = $this->chartData($this->operationalData()->sexes());

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
            'colors' => $this->colorsForLabels(array_keys($data)),
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
                        const number = Number(value)

                        return Number.isFinite(number) ? number.toFixed(0) + ' campistas' : value
                    }
                }
            }
        }
        JS);
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<int, string>
     */
    private function colorsForLabels(array $labels): array
    {
        return collect($labels)
            ->map(fn (string $label): string => match ($label) {
                'Masculino' => '#2563eb',
                'Feminino' => '#ec4899',
                default => '#64748b',
            })
            ->all();
    }
}
