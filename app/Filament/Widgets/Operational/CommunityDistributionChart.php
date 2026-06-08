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
        $options = $this->barOptions($this->operationalData()->communities(), 'Campistas', ['#0891b2']);

        $options['chart']['height'] = 380;
        $options['yaxis']['labels'] = [
            'maxWidth' => 280,
            'style' => [
                'fontSize' => '11px',
            ],
        ];

        return $options;
    }
}
