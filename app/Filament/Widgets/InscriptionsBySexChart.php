<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class InscriptionsBySexChart extends ApexChartWidget
{

    use HasWidgetShield;
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'inscriptionsBySexChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Inscrições por Sexo';

    protected  static ?int $sort  = 2;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
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
            'chart' => [
                'type' => 'donut',
                'height' => 300,
            ],
            'series' => [$options['M'], $options['F']],
            'labels' => ['Masculino', 'Feminino'],
            'colors' => ['#2f80ed', '#F53BD6'],
            'legend' => [
                'labels' => [
                    'fontFamily' => 'inherit',
                ],
            ],
        ];
    }
}
