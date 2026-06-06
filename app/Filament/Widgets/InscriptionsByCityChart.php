<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class InscriptionsByCityChart extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'Inscrições por Cidade';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $data = $this->getCityData();

        return [
            'datasets' => [
                [
                    'label' => 'Número de Inscrições',
                    'data' => array_values($data),
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'scales' => [
                'x' => [
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

    private function getCityData(): array
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
