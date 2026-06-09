<?php

namespace App\Filament\Resources\EquipeTrabalhoResource\Pages;

use App\Filament\Resources\EquipeTrabalhoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEquipeTrabalho extends ViewRecord
{
    protected static string $resource = EquipeTrabalhoResource::class;

    public function getTitle(): string
    {
        return 'Inscrição - Equipe #'.$this->getRecord()->getKey();
    }

    public function getSubheading(): ?string
    {
        return 'Visualização dos dados da inscrição da equipe de trabalho.';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar inscrição')
                ->icon('heroicon-o-pencil-square'),
        ];
    }
}
