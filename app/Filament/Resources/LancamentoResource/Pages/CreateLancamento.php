<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Filament\Resources\LancamentoResource;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Models\Lancamento;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Resources\Pages\CreateRecord;

class CreateLancamento extends CreateRecord
{
    protected static string $resource = LancamentoResource::class;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $itemData = [];

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $this->itemData = $data['items'] ?? [];
        unset($data['items']);

        $data = LancamentoForm::normalizeCompradorForType($data);
        $data['valor'] = app(RegistrationPaymentAllocator::class)->signedTotalForItems($data['tipo'] ?? null, $this->itemData);
        $data['comprovante'] = LancamentoForm::normalizeComprovanteState($data['comprovante'] ?? null);

        app(RegistrationPaymentAllocator::class)->validateItems(
            new Lancamento($data),
            $this->itemData,
        );

        return parent::mutateFormDataBeforeCreate($data);
    }

    protected function afterCreate(): void
    {
        app(RegistrationPaymentAllocator::class)->syncItems($this->record, $this->itemData);
    }
}
