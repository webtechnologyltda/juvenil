<?php

namespace App\Filament\Widgets;

use App\Enums\StatusInscricao;
use App\Filament\Widgets\Concerns\SupportsWidgetShield;
use App\Models\Campista;
use Filament\Widgets\ChartWidget;

class VlvFormDataChart extends ChartWidget
{
    use SupportsWidgetShield;

    protected ?string $heading = 'Panorama das Inscrições';

    protected ?string $pollingInterval = '60s';

    protected static ?string $loadingIndicator = 'Loading...';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'data' => [
                        Campista::where('status', StatusInscricao::Pago->value)->count(),
                        Campista::where('status', StatusInscricao::Pendente->value)->count(),
                        Campista::where('status', StatusInscricao::Cancelado->value)->count(),
                    ],
                    'backgroundColor' => ['#0A8126', '#DDB01B', '#F4366F'],
                ],
            ],
            'labels' => ['Pagas', 'Pendentes', 'Canceladas'],
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
