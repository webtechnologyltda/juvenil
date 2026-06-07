<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Filament\Widgets\Concerns\SupportsWidgetShield;
use App\Models\Campista;
use Filament\Widgets\ChartWidget;

class ParoquiaSaoDomingosWidget extends ChartWidget
{
    use SupportsWidgetShield;

    protected ?string $heading = 'Inscrições por Comunidade  - Matriz de São Domingos';

    protected ?string $maxHeight = '305px';

    protected static ?int $sort = 6;

    protected function getData(): array
    {

        $comunidadeMap = [
            0 => 'Comunidade Matriz de São Domingos ',
            1 => 'Comunidade Nossa Senhora das Graças',
            2 => 'Comunidade São Paulo',
            3 => 'Comunidade Nossa Senhora do Rosário',
            4 => 'Comunidade Imaculado Coração de Maria',
        ];

        $comunidades = array_fill_keys(array_values($comunidadeMap), 0);

        $data = Campista::select('form_data')->where('status', '<>', StatusInscricao::Cancelado->value)->get();

        foreach ($data as $value) {
            if (array_key_exists('comunidade', $value->form_data)) {
                $comunidadeId = $value->form_data['comunidade'];

                if ($value->form_data['paroquia'] == 0) {
                    if (array_key_exists($comunidadeId, $comunidadeMap)) {
                        $comunidadeNome = $comunidadeMap[$comunidadeId];
                        $comunidades[$comunidadeNome]++;
                    }
                }
            }
        }

        return [
            'datasets' => [
                [
                    'data' => array_values($comunidades),
                    'backgroundColor' => ['#2f80ed', '#F53BD6', '#FF7F50', '#8A2BE2', '#32CD32'],
                ],
            ],
            'labels' => array_keys($comunidades),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
