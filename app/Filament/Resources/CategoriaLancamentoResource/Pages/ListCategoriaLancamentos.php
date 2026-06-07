<?php

namespace App\Filament\Resources\CategoriaLancamentoResource\Pages;

use App\Filament\Resources\CategoriaLancamentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCategoriaLancamentos extends ListRecords
{
    protected static string $resource = CategoriaLancamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova categoria'),
        ];
    }
}
