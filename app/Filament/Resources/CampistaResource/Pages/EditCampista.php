<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Enums\StatusInscricao;
use App\Filament\Resources\CampistaResource;
use App\Filament\Resources\CampistaResource\CampistaForm;
use App\Models\Campista;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
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
        $data = CampistaForm::preserveSensitiveHealthDetails($data, $record);
        $newStatus = $this->normalizeStatus($data['status'] ?? null);
        $this->authorizePaidStatusChange($record, $newStatus);

        $sendNotificationStatusUpdate = $newStatus !== null && $newStatus !== $record->status;

        $record->update($data);

        if ($sendNotificationStatusUpdate) {

            $userAuth = auth()->user();
            $recipient = User::whereNot('id', $userAuth->id)->get();

            Notification::make()
                ->title('Status de incrição atualizado')
                ->warning()
                ->body(new HtmlString('O status da inscrição #'
                    .$record['id']
                    .' foi atualizada para <strong>'
                    .strtoupper($newStatus->name)
                    .'</strong> pelo usuário: '.$userAuth->name
                    .', acesse as inscrições para mais detalhes.'))
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

    private function authorizePaidStatusChange(Campista $record, ?StatusInscricao $newStatus): void
    {
        if ($newStatus === StatusInscricao::Pago && $record->status !== StatusInscricao::Pago) {
            Gate::authorize('markAsPaid', $record);
        }
    }

    private function normalizeStatus(mixed $status): ?StatusInscricao
    {
        if ($status instanceof StatusInscricao) {
            return $status;
        }

        if ($status === null || $status === '') {
            return null;
        }

        return StatusInscricao::tryFrom((int) $status);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return CampistaForm::redactSensitiveHealthDetails($data);
    }
}
