<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class RegistrationTrendChart extends ApexChartWidget
{
    use UsesOperationalDashboardData;

    protected static ?string $chartId = 'registrationTrendChart';

    protected static ?int $sort = 4;

    protected function getHeading(): ?string
    {
        return 'Inscrições por dia';
    }

    protected function getOptions(): array
    {
        $data = $this->chartData($this->operationalData()->registrationsByDay(), 30);

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => ['show' => false],
            ],
            'series' => [
                [
                    'name' => 'Inscrições',
                    'data' => array_values($data),
                ],
            ],
            'xaxis' => [
                'categories' => array_keys($data),
            ],
            'yaxis' => $this->integerCountAxisOptions($data),
            'stroke' => [
                'curve' => 'smooth',
                'width' => 3,
            ],
            'fill' => [
                'type' => 'gradient',
                'gradient' => [
                    'shadeIntensity' => 0.2,
                    'opacityFrom' => 0.28,
                    'opacityTo' => 0.04,
                ],
            ],
            'colors' => ['#2563eb'],
            'dataLabels' => ['enabled' => false],
            'grid' => [
                'borderColor' => '#e5e7eb',
                'strokeDashArray' => 4,
            ],
        ];
    }
}
