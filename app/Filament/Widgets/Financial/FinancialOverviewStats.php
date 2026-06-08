<?php

namespace App\Filament\Widgets\Financial;

use App\Filament\Widgets\Concerns\SupportsWidgetShield;
use App\Filament\Widgets\Financial\Concerns\UsesFinancialDashboardData;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverviewStats extends StatsOverviewWidget
{
    use SupportsWidgetShield;
    use UsesFinancialDashboardData;

    protected ?string $heading = 'Resumo financeiro';

    protected ?string $description = 'Valores filtrados por data, tipo, status, categoria e forma de pagamento.';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $summary = $this->financialData()->summary();

        return [
            Stat::make('Receitas', $this->money($summary['revenue']))
                ->description('Entradas de inscrições e cobranças')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Despesas', $this->money($summary['expenses']))
                ->description('Saídas registradas')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Doações', $this->money($summary['donations']))
                ->description('Entradas classificadas como doação')
                ->descriptionIcon('iconoir-donate')
                ->color('info'),
            Stat::make('Saldo', $this->money($summary['balance']))
                ->description($summary['entries'].' lançamentos no filtro')
                ->descriptionIcon('fas-cash-register')
                ->color($summary['balance'] >= 0 ? 'success' : 'danger'),
        ];
    }
}
