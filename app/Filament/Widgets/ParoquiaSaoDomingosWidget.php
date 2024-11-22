<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class ParoquiaSaoDomingosWidget extends ApexChartWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Inscrições por Comunidade  - Matriz de São Domingos';

      protected static ?int $contentHeight = 305;

      protected  static ?int $sort  = 6;


    protected function getOptions(): array
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
            if(array_key_exists('comunidade', $value->form_data)) {
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
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => array_values($comunidades), // Quantidade por comunidade
            'labels' => array_keys($comunidades),   // Nomes das comunidades
            'colors' => ['#2f80ed', '#F53BD6', '#FF7F50', '#8A2BE2', '#32CD32'],
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            ],
        ];
    }
}
