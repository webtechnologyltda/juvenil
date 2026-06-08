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
            Actions\Action::make('batch')
                ->label('Lançamento em lote')
                ->icon('heroicon-o-queue-list')
                ->color('gray')
                ->url(LancamentoResource::getUrl('batch'))
                ->visible(fn (): bool => LancamentoResource::canCreate()),
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
