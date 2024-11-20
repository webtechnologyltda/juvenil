<?php

namespace App\Filament\Resources\EquipeTrabalhoResource\Pages;

use App\Filament\Resources\EquipeTrabalhoResource;
use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoForm;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;

class CreateEquipeTrabalho extends CreateRecord
{
    protected static string $resource = EquipeTrabalhoResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema(
                EquipeTrabalhoForm::getFormCreate(),
            );
    }
}
