<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ShirtSizeChart extends ApexChartWidget
{
    use UsesOperationalDashboardData;

    protected static ?string $chartId = 'shirtSizeChart';

    protected static ?int $sort = 6;

    protected function getHeading(): ?string
    {
        return 'Camisetas';
    }

    protected function getSubheading(): ?string
    {
        return 'Tamanhos declarados, incluindo outros tamanhos.';
    }

    protected function getOptions(): array
    {
        return $this->barOptions($this->operationalData()->shirts(), 'Campistas', ['#ea580c']);
    }
}
