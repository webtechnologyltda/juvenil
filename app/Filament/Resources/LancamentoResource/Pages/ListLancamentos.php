<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Filament\Resources\LancamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLancamentos extends ListRecords
{
    protected static string $resource = LancamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LancamentoResource\Widgets\StatsFinanceiro::class
        ];
    }
}
