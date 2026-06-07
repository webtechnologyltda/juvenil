<?php

namespace App\Filament\Widgets\Operational\Concerns;

use App\Support\Dashboard\OperationalDashboardData;
use Filament\Support\RawJs;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

trait UsesOperationalDashboardData
{
    use InteractsWithPageFilters;

    protected function operationalData(): object
    {
        return app(OperationalDashboardData::class)->forFilters($this->pageFilters ?? []);
    }

    protected function campistaQuery(bool $validOnly = true): Builder
    {
        return app(OperationalDashboardData::class)->queryForFilters($this->pageFilters ?? [], $validOnly);
    }

    protected function chartData(array $data, int $limit = 12): array
    {
        $data = array_slice($data, 0, $limit, preserve_keys: true);

        return $data === [] ? ['Sem dados' => 0] : $data;
    }

    protected function barOptions(array $data, string $seriesName, array $colors = ['#f97316']): array
    {
        $data = $this->chartData($data);
        $axis = $this->integerCountAxisOptions($data);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 320,
                'toolbar' => ['show' => false],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                    'barHeight' => '72%',
                ],
            ],
            'series' => [
                [
                    'name' => $seriesName,
                    'data' => array_values($data),
                ],
            ],
            'xaxis' => [
                'categories' => array_keys($data),
                ...$axis,
            ],
            'dataLabels' => ['enabled' => true],
            'colors' => $colors,
            'grid' => [
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 4,
            ],
        ];
    }

    protected function integerCountAxisOptions(array $data): array
    {
        $max = max(array_map(fn (mixed $value): int => (int) $value, array_values($data)));
        $max = max(1, $max);

        if ($max <= 10) {
            return [
                'min' => 0,
                'max' => $max,
                'tickAmount' => $max,
                'decimalsInFloat' => 0,
                'forceNiceScale' => false,
            ];
        }

        $step = (int) ceil($max / 10);

        return [
            'min' => 0,
            'max' => $step * 10,
            'tickAmount' => 10,
            'decimalsInFloat' => 0,
            'forceNiceScale' => false,
        ];
    }

    protected function extraJsOptions(): ?RawJs
    {
        return RawJs::make(<<<'JS'
        {
            xaxis: {
                labels: {
                    formatter: function (value) {
                        const number = Number(value)

                        return Number.isFinite(number) ? number.toFixed(0) : value
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        const number = Number(value)

                        return Number.isFinite(number) ? number.toFixed(0) : value
                    }
                }
            },
            dataLabels: {
                formatter: function (value) {
                    const number = Number(value)

                    return Number.isFinite(number) ? number.toFixed(0) : value
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
}
