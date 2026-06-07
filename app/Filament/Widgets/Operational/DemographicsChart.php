<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class DemographicsChart extends ApexChartWidget
{
    use UsesOperationalDashboardData;

    protected static ?string $chartId = 'demographicsChart';

    protected static ?int $sort = 8;

    protected function getHeading(): ?string
    {
        return 'Demografia';
    }

    protected function getSubheading(): ?string
    {
        return 'Faixas etárias e sexo informado.';
    }

    protected function getOptions(): array
    {
        $data = [
            ...$this->operationalData()->ages(),
            ...$this->operationalData()->sexes(),
        ];

        return $this->barOptions($data, 'Campistas', ['#16a34a']);
    }
}
