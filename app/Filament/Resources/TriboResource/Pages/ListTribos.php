<?php

namespace App\Filament\Resources\TriboResource\Pages;

use App\Filament\Resources\TriboResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTribos extends ListRecords
{
    protected static string $resource = TriboResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-s-plus'),
        ];
    }
}
