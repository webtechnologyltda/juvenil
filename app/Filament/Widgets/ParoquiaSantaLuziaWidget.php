<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ParoquiaSantaLuziaWidget extends ApexChartWidget
{

    use HasWidgetShield;
    protected static ?string $heading = 'Inscrições por Comunidade - Matriz Santa Luzia';

    protected  static ?int $sort  = 4;

    protected function getOptions(): array
    {

        $comunidadeMap = [
            0 => 'Comunidade Santa Luzia - Machados',
            1 => 'Comunidade Santa Teresinha',
            2 => 'Comunidade São Francisco',
            3 => 'Comunidade Sagrado Coração',
            4 => 'Comunidade Nossa Senhora de Fátima',
            5 => 'Comunidade Santo Agostinho',
            6 => 'Comunidade São José',
            7 => 'Comunidade Nossa Senhora Aparecida',
        ];

        $comunidades = array_fill_keys(array_values($comunidadeMap), 0);

        $data = Campista::select('form_data')->where('status', '<>', StatusInscricao::Cancelado->value)->get();

        foreach ($data as $value) {
            if(array_key_exists('comunidade', $value->form_data)) {
                $comunidadeId = $value->form_data['comunidade'];

                if ($value->form_data['paroquia'] == 1) {
                    if (array_key_exists($comunidadeId, $comunidadeMap)) {
                        $comunidadeNome = $comunidadeMap[$comunidadeId];
                        $comunidades[$comunidadeNome]++;
                    }
                }
            }
        }

        return [
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => array_values($comunidades),
            'labels' => array_keys($comunidades),
            'colors' => ['#FF6347', '#8A2BE2', '#20B2AA', '#FFD700', '#00FA9A', '#FF1493', '#32CD32', '#1E90FF'],
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            ],
        ];
    }
}


