<?php

namespace App\Filament\Resources\EquipeTrabalhoResource\Widgets;

use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Filament\Widgets\Concerns\SupportsWidgetShield;
use App\Models\EquipeTrabalho;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EquipeTrabalhoStatsWidget extends BaseWidget
{
    use SupportsWidgetShield;

    protected ?string $pollingInterval = '60s';

    protected static ?string $label = 'Overview Equipe de Trabalho';

    protected function getStats(): array
    {
        $totals = EquipeTrabalho::query()
            ->where('status', '!=', StatusInscricaoEquipeTrabalho::Cancelado->value)
            ->selectRaw(
                'COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN data_form->>"$.sexo" = ? THEN 1 ELSE 0 END), 0) AS mulheres,
                COALESCE(SUM(CASE WHEN data_form->>"$.sexo" = ? THEN 1 ELSE 0 END), 0) AS homens',
                ['F', 'M'],
            )
            ->firstOrFail();

        return [
            Stat::make('', (int) $totals->total)
                ->description('Qtd de Inscrições')
                ->descriptionIcon('heroicon-m-users')
                ->color(Color::Orange),

            Stat::make('', (int) $totals->mulheres)
                ->description('Qtd Mulheres')
                ->descriptionIcon('eos-female')
                ->color(Color::Pink),

            Stat::make('', (int) $totals->homens)
                ->description('Qtd Homens')
                ->descriptionIcon('eos-male')
                ->color(Color::Blue),
        ];
    }
}
