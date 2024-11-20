<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class InscriptionsByAgeChart extends ApexChartWidget
{

    use HasWidgetShield;

    /**
     * Chart Id
     */
    protected static ?string $chartId = 'inscriptionsByAgeChart';

    /**
     * Widget Title
     */
    protected static ?string $heading = 'Inscrições por Idade';

    protected  static ?int $sort  = 0;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        $data = Campista::select('form_data')->where('status', '<>', StatusInscricao::Cancelado->value)->get();
        $ages = [
            '18-24' => 0,
            '25-34' => 0,
            '35-44' => 0,
            '45-54' => 0,
            '55-64' => 0,
            '65+' => 0,
        ];
        foreach ($data as $key => $value) {
            $data[$key]['age'] = Carbon::now()->diffInYears(Carbon::createFromFormat('d/m/Y', $value->form_data['data_nacimento'])->toISOString()) * -1;
            if ($data[$key]['age'] < 24 && $data[$key]['age'] > 18) {
                $ages['18-24']++;
            } elseif ($data[$key]['age'] < 35 && $data[$key]['age'] > 24) {
                $ages['25-34']++;
            } elseif ($data[$key]['age'] < 45 && $data[$key]['age'] > 34) {
                $ages['35-44']++;
            } elseif ($data[$key]['age'] < 64 && $data[$key]['age'] > 45) {
                $ages['55-64']++;
            } elseif ($data[$key]['age'] > 65) {
                $ages['65+']++;
            }
        }

        $maxAgeInscription = max($ages);

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
            ],
            'plotOptions' => [
                'bar' => [
                    'barHeight' => '100%',
                    'horizontal' => false,
                    'borderRadius' => 4,
                    'borderRadiusApplication' => 'end',
                    'dataLabels' => [
                        'position' => 'bottom',
                    ],
                ],
            ],
            'dataLabels' => [
                'enabled' => true,
                'textAnchor' => 'start',
            ],
            'series' => [
                [
                    'name' => 'Número de Inscrições',
                    'data' => [
                        $ages['18-24'], $ages['25-34'], $ages['35-44'], $ages['45-54'], $ages['55-64'], $ages['65+'],
                    ],
                ],
            ],
            'xaxis' => [
                'categories' => ['18-24', '25-34', '35-44', '45-54', '55-64', '65+'],
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'min' => 0,
                'max' => $maxAgeInscription,
                'stepSize' => ceil($maxAgeInscription / 5),
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],

            ],
            'colors' => ['#5E00B6'],
        ];
    }

}
