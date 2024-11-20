<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GeneralStatsOverview extends BaseWidget
{
    use HasWidgetShield;
    protected static ?string $pollingInterval = '60s';

    /**
     * Widget Title
     */
    protected static ?string $label = 'Inscrições por Idade';

    protected function getStats(): array
    {
        return [
            Stat::make('', Campista::count())
                ->description('Número total de Inscrições')
                ->descriptionIcon('heroicon-m-fire')
                ->color(Color::Orange),

            Stat::make('', Campista::where('status', StatusInscricao::Pago->value)->count())
                ->description('Inscrições Pagas')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color(Color::Green),

            Stat::make('', Campista::where('status', StatusInscricao::Pendente->value)->count())
                ->description('Inscrições Pendentes')
                ->descriptionIcon('heroicon-m-clock')
                ->color(Color::Amber),

            Stat::make('', Campista::where('status', StatusInscricao::Cancelado->value)->count())
                ->description('Inscrições Canceladas')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color(Color::Rose),
        ];
    }
}
