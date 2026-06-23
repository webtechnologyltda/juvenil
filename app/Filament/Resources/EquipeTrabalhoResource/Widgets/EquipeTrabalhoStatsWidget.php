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
        $data = EquipeTrabalho::query()
            ->where('status', '!=', StatusInscricaoEquipeTrabalho::Cancelado)
            ->get();

        $sexo = [
            'F' => 0,
            'M' => 0,
        ];

        foreach ($data as $registration) {
            $sex = data_get($registration->data_form, 'sexo');

            if (array_key_exists($sex, $sexo)) {
                $sexo[$sex]++;
            }
        }

        return [
            Stat::make('', EquipeTrabalho::query()
                ->where('status', '!=', StatusInscricaoEquipeTrabalho::Cancelado)
                ->count())
                ->description('Qtd de Inscrições')
                ->descriptionIcon('heroicon-m-users')
                ->color(Color::Orange),

            Stat::make('', $sexo['F'])
                ->description('Qtd Mulheres')
                ->descriptionIcon('eos-female')
                ->color(Color::Pink),

            Stat::make('', $sexo['M'])
                ->description('Qtd Homens')
                ->descriptionIcon('eos-male')
                ->color(Color::Blue),
        ];
    }
}
