<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Filament\Resources\LancamentoResource;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\Pages\Concerns\HasLancamentoHelpAction;
use App\Filament\Resources\LancamentoResource\Pages\Concerns\NotifiesLancamentoValidation;
use App\Models\Lancamento;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLancamento extends CreateRecord
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
        ];
    }

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $this->itemData = $data['items'] ?? [];
        unset($data['items']);

        $data = LancamentoForm::normalizeCompradorForType($data);
        $data['valor'] = app(RegistrationPaymentAllocator::class)->signedTotalForItems($data['tipo'] ?? null, $this->itemData);
        $data['comprovante'] = LancamentoForm::normalizeComprovanteState($data['comprovante'] ?? null);

        try {
            app(RegistrationPaymentAllocator::class)->validateItems(
                new Lancamento($data),
                $this->itemData,
            );
        } catch (ValidationException $exception) {
            $this->notifyLancamentoValidationException($exception);

            throw $exception;
        }

        return parent::mutateFormDataBeforeCreate($data);
    }

    protected function afterCreate(): void
    {
        app(RegistrationPaymentAllocator::class)->syncItems($this->record, $this->itemData);
    }
}
