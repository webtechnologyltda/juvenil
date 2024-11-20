<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class VlvFormDataChart extends ApexChartWidget
{

    use HasWidgetShield;
    protected static ?string $heading = 'Panorama das Inscrições';

    protected static ?string $pollingInterval = '60s';

    protected static ?string $loadingIndicator = 'Loading...';

    /**
     * Chart Id
     */
    protected static ?string $chartId = 'panoramaInscricoesBasicRadialBarChart';

    protected  static ?int $sort  = 3;

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     */
    protected function getOptions(): array
    {
        return [
            'chart' => [
                'type' => 'pie',
                'height' => 300,
            ],
            'series' => [
                Campista::where('status', StatusInscricao::Pago->value)->count(),
                Campista::where('status', StatusInscricao::Pendente->value)->count(),
                Campista::where('status', StatusInscricao::Cancelado->value)->count(),
            ],
            'plotOptions' => [
                'radialBar' => [
                    'hollow' => [
                        'size' => '50%',
                    ],
                    'dataLabels' => [
                        'show' => true,
                        'name' => [
                            'show' => true,
                            'color' => '#9ca3af',
                            'fontWeight' => 600,
                        ],
                        'value' => [
                            'show' => true,
                            'color' => '#9ca3af',
                            'fontWeight' => 600,
                            'fontSize' => '20px',
                        ],
                    ],

                ],
            ],
            'stroke' => [
                'lineCap' => 'round',
            ],
            'labels' => ['Pagas', 'Pendentes', 'Canceladas'],
            'colors' => ['#0A8126', '#DDB01B', '#F4366F'],
        ];
    }
}
