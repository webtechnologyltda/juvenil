<?php

namespace App\Filament\Widgets\Operational;

use App\Filament\Widgets\Operational\Concerns\UsesOperationalDashboardData;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperationalPipelineStats extends StatsOverviewWidget
{
    use UsesOperationalDashboardData;

    protected ?string $heading = 'Operação do evento';

    protected ?string $description = 'Esteira operacional sem valores financeiros.';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pipeline = $this->operationalData()->pipeline();

        return [
            Stat::make('Inscrições válidas', $pipeline['valid'])
                ->description('Pendentes + pagas')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('info')
                ->url(route('filament.admin.resources.campistas.index')),
            Stat::make('Pagamento pendente', $pipeline['pending_payment'])
                ->description('Fila de ação, sem valores')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.campistas.index')),
            Stat::make('Pagas', $pipeline['paid'])
                ->description('Liberadas para check-in')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->url(route('filament.admin.resources.campistas.index')),
            Stat::make('Aguardando check-in', $pipeline['awaiting_check_in'])
                ->description('Pago e ainda sem presença')
                ->descriptionIcon('heroicon-o-qr-code')
                ->color('warning')
                ->url(route('filament.admin.resources.campistas.index')),
            Stat::make('Presentes', $pipeline['present'])
                ->description('Check-in confirmado')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success')
                ->url(route('filament.admin.resources.campistas.index')),
            Stat::make('Canceladas', $pipeline['cancelled'])
                ->description('Indicador lateral')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->url(route('filament.admin.resources.campistas.index')),
        ];
    }
}
