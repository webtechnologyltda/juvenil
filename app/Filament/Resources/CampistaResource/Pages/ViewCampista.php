<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Filament\Resources\CampistaResource;
use App\Filament\Resources\CampistaResource\CampistaForm;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewCampista extends ViewRecord
{
    protected static string $resource = CampistaResource::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                ...CampistaForm::getFormView()
            ]);
    }
}
