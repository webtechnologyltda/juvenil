<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Filament\Resources\LancamentoResource;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\Pages\Concerns\HasLancamentoHelpAction;
use App\Filament\Resources\LancamentoResource\Pages\Concerns\NotifiesLancamentoValidation;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditLancamento extends EditRecord
{
    use HasLancamentoHelpAction;
    use NotifiesLancamentoValidation;

    protected static string $resource = LancamentoResource::class;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $itemData = [];

    protected function getHeaderActions(): array
    {
        return [
            $this->lancamentoHelpAction(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['comprovante'] = LancamentoForm::comprovanteRepeaterFormState($data['comprovante'] ?? null);
        $data['items'] = LancamentoForm::itemsFormState($this->record);

        return parent::mutateFormDataBeforeFill($data);
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        $this->itemData = $data['items'] ?? [];
        unset($data['items']);

        $data = LancamentoForm::normalizeCompradorForType($data);
        $data['valor'] = app(RegistrationPaymentAllocator::class)->signedTotalForItems($data['tipo'] ?? null, $this->itemData);
        $data['comprovante'] = LancamentoForm::normalizeComprovanteState($data['comprovante'] ?? null);

        $this->record->forceFill($data);

        try {
            app(RegistrationPaymentAllocator::class)->validateItems(
                $this->record,
                $this->itemData,
            );
        } catch (ValidationException $exception) {
            $this->notifyLancamentoValidationException($exception);

            throw $exception;
        }

        return parent::mutateFormDataBeforeSave($data);
    }

    protected function afterSave(): void
    {
        app(RegistrationPaymentAllocator::class)->syncItems($this->record, $this->itemData);
    }
}
