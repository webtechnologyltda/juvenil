<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SensitiveHealthSummaryStats extends StatsOverviewWidget
{
    use UsesOperationalDashboardData;

    protected ?string $heading = 'Alertas de cuidado';

    protected ?string $description = 'Resumo sem detalhes sensíveis.';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $summary = $this->operationalData()->healthSummary();

        return [
            Stat::make('Usam remédio', $summary['medicine'])
                ->description('Detalhes somente na ficha')
                ->descriptionIcon('heroicon-o-shield-check')
                ->color('danger'),
            Stat::make('Com recomendação', $summary['recommendation'])
                ->description('Cuidados especiais')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),
            Stat::make('Ambos', $summary['both'])
                ->description('Prioridade para enfermaria')
                ->descriptionIcon('heroicon-o-heart')
                ->color('danger'),
        ];
    }
}
