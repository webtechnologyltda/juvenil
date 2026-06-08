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
        return 'Demografia por faixa etária';
    }

    protected function getSubheading(): ?string
    {
        return 'Faixas ordenadas por idade dos campistas.';
    }

    protected function getOptions(): array
    {
        return $this->barOptions($this->operationalData()->ages(), 'Campistas', ['#16a34a']);
    }
}
