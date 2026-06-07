<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CommunityDistributionChart extends ApexChartWidget
{
    use UsesOperationalDashboardData;

    protected static ?string $chartId = 'communityDistributionChart';

    protected static ?int $sort = 7;

    protected function getHeading(): ?string
    {
        return 'Paróquia e comunidade';
    }

    protected function getOptions(): array
    {
        return $this->barOptions($this->operationalData()->communities(), 'Campistas', ['#0891b2']);
    }
}
