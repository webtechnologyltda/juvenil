<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLancamento extends EditRecord
{
    protected static string $resource = LancamentoResource::class;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $registrationPaymentData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['comprovante'] = LancamentoForm::comprovanteRepeaterFormState($data['comprovante'] ?? null);
        $data['registration_payments'] = LancamentoForm::registrationPaymentsFormState($this->record);

        return parent::mutateFormDataBeforeFill($data);
    }

    public function mutateFormDataBeforeSave(array $data): array
    {
        $this->registrationPaymentData = $data['registration_payments'] ?? [];
        unset($data['registration_payments']);

        $data = LancamentoForm::normalizeCompradorForType($data);
        $data['valor'] = self::signedValue($data);
        $data['comprovante'] = LancamentoForm::normalizeComprovanteState($data['comprovante'] ?? null);

        $this->record->forceFill($data);

        app(RegistrationPaymentAllocator::class)->validateAllocations(
            $this->record,
            $this->registrationPaymentData,
        );

        return parent::mutateFormDataBeforeSave($data);
    }

    protected function afterSave(): void
    {
        app(RegistrationPaymentAllocator::class)->sync($this->record, $this->registrationPaymentData);
    }

    private static function signedValue(array $data): int
    {
        $value = (int) ($data['valor'] ?? 0);
        $type = $data['tipo'] ?? null;

        if (($type == TipoLacamento::Despesa->value && $value > 0) || ($type != TipoLacamento::Despesa->value && $value < 0)) {
            return $value * -1;
        }

        return $value;
    }
}
