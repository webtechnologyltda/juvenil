<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Filament\Resources\CampistaResource;
use App\Filament\Resources\CampistaResource\CampistaForm;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;

class CreateCampista extends CreateRecord
{
    use HasWizard;

    protected static string $resource = CampistaResource::class;

    protected function getSteps(): array
    {
        return [
            ...CampistaForm::getFormCreate()
        ];
    }

    protected function afterCreate(): void
    {
        $userAuth = auth()->user();
        $recipient = User::whereNot('id', $userAuth->id)->get();

        Notification::make()
            ->info()
            ->title('Nova inscrição')
            ->body('Uma nova inscrição foi criada pelo usuário: ' . $userAuth->name . ', acesse as inscrições para mais detalhes.')
            ->sendToDatabase($recipient);
    }
}
