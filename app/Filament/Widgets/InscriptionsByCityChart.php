<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class InscriptionsByCityChart extends ApexChartWidget
{

    use HasWidgetShield;
    /**
     * Chart Id
     */
    protected static ?string $chartId = 'inscriptionsByCityChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Inscrições por Cidade';

    protected  static ?int $sort  = 1;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $data = $this->getData();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
            ],
            'series' => [
                [
                    'name' => 'Número de Inscrições',
                    'data' => array_values($data),
                ],
            ],
            'xaxis' => [
                'categories' => array_keys($data),
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
                'stepSize' => 1,
                'min' => 0,
                'floating' => false,
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'colors' => ['#f59e0b'],
            'plotOptions' => [
                'bar' => [
                    'borderRadius' => 3,
                    'horizontal' => true,
                ],
            ],
        ];
    }

    public function getData(): array
    {
        $data = Campista::select('form_data')
            ->where('status', '<>', StatusInscricao::Cancelado->value)
            ->get();
        $cities = [];
        foreach ($data as $value) {
            if (array_key_exists($value->form_data['cidade'].'/'.$value->form_data['estado'], $cities)) {
                $cities[$value->form_data['cidade'].'/'.$value->form_data['estado']]++;
            } else {
                $cities[$value->form_data['cidade'].'/'.$value->form_data['estado']] = 1;
            }
        }
        array_multisort($cities, SORT_DESC, $cities);

        return $cities;
    }
}
