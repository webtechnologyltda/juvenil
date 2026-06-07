<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class OperationalFunnelChart extends ApexChartWidget
{
    use UsesOperationalDashboardData;

    protected static ?string $chartId = 'operationalFunnelChart';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    protected function getHeading(): ?string
    {
        return 'Funil operacional';
    }

    protected function getSubheading(): ?string
    {
        return 'Da inscrição válida até o check-in confirmado.';
    }

    protected function getOptions(): array
    {
        $pipeline = $this->operationalData()->pipeline();

        return $this->barOptions([
            'Válidas' => $pipeline['valid'],
            'Pagamento pendente' => $pipeline['pending_payment'],
            'Pagas' => $pipeline['paid'],
            'Aguardando check-in' => $pipeline['awaiting_check_in'],
            'Presentes' => $pipeline['present'],
        ], 'Campistas', ['#0f766e']);
    }
}
