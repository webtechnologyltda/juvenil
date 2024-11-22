<?php

namespace App\Filament\Resources\EquipeTrabalhoResource\Pages;

use App\Filament\Resources\EquipeTrabalhoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipeTrabalhos extends ListRecords
{
    protected static string $resource = EquipeTrabalhoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EquipeTrabalhoResource\Widgets\EquipeTrabalhoStatsWidget::class
        ];
    }
}
