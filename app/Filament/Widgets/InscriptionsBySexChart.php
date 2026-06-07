<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Filament\Widgets\Concerns\SupportsWidgetShield;
use App\Models\Campista;
use Filament\Widgets\ChartWidget;

class InscriptionsBySexChart extends ChartWidget
{
    use SupportsWidgetShield;

    protected ?string $heading = 'Inscrições por Sexo';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Campista::select('form_data')->where('status', '<>', StatusInscricao::Cancelado->value)->get();
        $options = [
            'M' => 0,
            'F' => 0,
        ];

        foreach ($data as $value) {
            $options[$value->form_data['sexo']]++;
        }

        return [
            'datasets' => [
                [
                    'data' => [$options['M'], $options['F']],
                    'backgroundColor' => ['#2f80ed', '#F53BD6'],
                ],
            ],
            'labels' => ['Masculino', 'Feminino'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
