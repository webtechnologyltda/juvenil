<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Filament\Widgets\Concerns\SupportsWidgetShield;
use App\Models\Campista;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class InscriptionsByAgeChart extends ChartWidget
{
    use SupportsWidgetShield;

    protected ?string $heading = 'Inscrições por Idade';

    protected static ?int $sort = 0;

    protected function getData(): array
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
            $data[$key]['age'] = Carbon::createFromFormat('d/m/Y', $value->form_data['data_nacimento'])->age;
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

        return [
            'datasets' => [
                [
                    'label' => 'Número de Inscrições',
                    'data' => array_values($ages),
                    'backgroundColor' => '#5E00B6',
                ],
            ],
            'labels' => array_keys($ages),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
