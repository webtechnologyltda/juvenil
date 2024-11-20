<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Enums\StatusInscricao;
use App\Filament\Resources\CampistaResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class EditCampista extends EditRecord
{
    protected static string $resource = CampistaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $sendNotificationStatusUpdate = false;
        if(isset($data['status']) && $data['status'] != $record['status']) {
            $sendNotificationStatusUpdate = true;
        }

        $record->update($data);

        if($sendNotificationStatusUpdate) {

            $userAuth = auth()->user();
            $recipient = User::whereNot('id', $userAuth->id)->get();

            Notification::make()
                ->title('Status de incrição atualizado')
                ->warning()
                ->body(new HtmlString('O status da inscrição #'
                    . $record['id']
                    . ' foi atualizada para <strong>'
                    . strtoupper(StatusInscricao::from($data['status'])->name)
                    . '</strong> pelo usuário: ' . $userAuth->name
                    . ', acesse as inscrições para mais detalhes.'))
                ->actions([
                    Action::make('Visualizar Inscrição')
                        ->button()
                        ->url(route('filament.admin.resources.campistas.edit', $record['id']))
                        ->close(),
                ])
                ->sendToDatabase($recipient);

        }

        return $record;
    }
}
