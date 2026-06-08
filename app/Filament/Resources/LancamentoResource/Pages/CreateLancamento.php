<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Enums\TipoLacamento;
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
    private array $registrationPaymentData = [];

    public function mutateFormDataBeforeCreate(array $data): array
    {
        $this->registrationPaymentData = $data['registration_payments'] ?? [];
        unset($data['registration_payments']);

        $data['valor'] = self::signedValue($data);
        $data['comprovante'] = LancamentoForm::normalizeComprovanteState($data['comprovante'] ?? null);

        app(RegistrationPaymentAllocator::class)->validateAllocations(
            new Lancamento($data),
            $this->registrationPaymentData,
        );

        return parent::mutateFormDataBeforeCreate($data);
    }

    protected function afterCreate(): void
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
