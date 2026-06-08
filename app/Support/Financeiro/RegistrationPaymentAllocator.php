<?php

namespace App\Support\Financeiro;

use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\StatusLacamento;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\FinancialEntryRegistration;
use App\Models\Lancamento;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationPaymentAllocator
{
    private const REGISTRATION_SEARCH_LIMIT = 50;

    /**
     * @return array<class-string<Model>, string>
     */
    public static function registrationTypeOptions(): array
    {
        return [
            Campista::class => 'Campista',
            EquipeTrabalho::class => 'Equipe de trabalho',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function registrationOptions(?string $registrationType, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null): array
    {
        if (! $this->isSupportedRegistrationType($registrationType)) {
            return [];
        }

        /** @var class-string<Model> $registrationType */
        return $this->registrationOptionResults(
            registrationType: $registrationType,
            excludingLancamentoId: $excludingLancamentoId,
            currentRegistrationId: $currentRegistrationId,
        );
    }

    /**
     * @return array<int, string>
     */
    public function registrationSearchResults(?string $registrationType, ?string $search, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null): array
    {
        if (! $this->isSupportedRegistrationType($registrationType) || blank($search)) {
            return [];
        }

        /** @var class-string<Model> $registrationType */
        return $this->registrationOptionResults(
            registrationType: $registrationType,
            excludingLancamentoId: $excludingLancamentoId,
            currentRegistrationId: $currentRegistrationId,
            search: $search,
            limit: self::REGISTRATION_SEARCH_LIMIT,
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocations
     * @return array<int, array{registration_type: class-string<Model>, registration_id: int, amount: int}>
     */
    public function validateAllocations(Lancamento $lancamento, array $allocations): array
    {
        $normalized = $this->normalizeAllocations($allocations);
        $totalAllocated = array_sum(array_column($normalized, 'amount'));
        $entryAmount = abs((int) $lancamento->valor);

        if ($totalAllocated > $entryAmount) {
            throw ValidationException::withMessages([
                'registration_payments' => 'A soma dos valores vinculados às inscrições não pode ultrapassar o valor do lançamento.',
            ]);
        }

        foreach ($normalized as $allocation) {
            $registration = $this->registrationFromAllocation($allocation);
            $remaining = $this->remainingAmountFor($registration, $lancamento->exists ? (int) $lancamento->getKey() : null);

            if ($allocation['amount'] > $remaining) {
                throw ValidationException::withMessages([
                    'registration_payments' => sprintf(
                        'O valor aplicado em %s não pode ultrapassar o saldo restante da inscrição.',
                        $this->registrationName($registration),
                    ),
                ]);
            }
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocations
     */
    public function sync(Lancamento $lancamento, array $allocations): void
    {
        $normalized = $this->validateAllocations($lancamento, $allocations);

        DB::transaction(function () use ($lancamento, $normalized): void {
            $previousRegistrations = $lancamento->registrationPayments()
                ->with('registration')
                ->get()
                ->map(fn (FinancialEntryRegistration $payment): ?Model => $payment->registration)
                ->filter();

            $lancamento->registrationPayments()->delete();

            foreach ($normalized as $allocation) {
                $lancamento->registrationPayments()->create($allocation);
            }

            $currentRegistrations = collect($normalized)
                ->map(fn (array $allocation): Model => $this->registrationFromAllocation($allocation));

            $previousRegistrations
                ->merge($currentRegistrations)
                ->unique(fn (Model $registration): string => $registration::class.'#'.$registration->getKey())
                ->each(fn (Model $registration): mixed => $this->syncRegistrationStatus($registration));
        });
    }

    public function paidAmountFor(Model $registration, ?int $excludingLancamentoId = null): int
    {
        $query = FinancialEntryRegistration::query()
            ->where('registration_type', $registration::class)
            ->where('registration_id', $registration->getKey())
            ->whereHas('lancamento', fn ($query) => $query->where('status', StatusLacamento::Pago->value));

        if ($excludingLancamentoId !== null) {
            $query->where('lancamento_id', '<>', $excludingLancamentoId);
        }

        return (int) $query->sum('amount');
    }

    public function remainingAmountFor(Model $registration, ?int $excludingLancamentoId = null): int
    {
        $expected = $this->expectedAmountFor($registration);

        if ($expected === null) {
            return 0;
        }

        return max(0, $expected - $this->paidAmountFor($registration, $excludingLancamentoId));
    }

    public function expectedAmountFor(Model $registration): ?int
    {
        if (! $this->isSupportedRegistrationType($registration::class)) {
            return null;
        }

        $amount = (int) (app(GeneralSettings::class)->valor_acampamento ?? 0);

        return $amount > 0 ? $amount : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $allocations
     * @return array<int, array{registration_type: class-string<Model>, registration_id: int, amount: int}>
     */
    private function normalizeAllocations(array $allocations): array
    {
        $normalized = [];
        $seen = [];

        foreach ($allocations as $allocation) {
            $registrationType = $allocation['registration_type'] ?? null;
            $registrationId = (int) ($allocation['registration_id'] ?? 0);
            $amount = (int) ($allocation['amount'] ?? 0);

            if (! $this->isSupportedRegistrationType($registrationType) && blank($registrationType) && $registrationId === 0 && $amount === 0) {
                continue;
            }

            if (! $this->isSupportedRegistrationType($registrationType) || $registrationId <= 0 || $amount <= 0) {
                throw ValidationException::withMessages([
                    'registration_payments' => 'Informe tipo, inscrição e valor aplicado em todos os vínculos de inscrição.',
                ]);
            }

            $key = $registrationType.'#'.$registrationId;

            if (array_key_exists($key, $seen)) {
                throw ValidationException::withMessages([
                    'registration_payments' => 'A mesma inscrição não pode ser vinculada duas vezes ao mesmo lançamento.',
                ]);
            }

            $seen[$key] = true;
            $normalized[] = [
                'registration_type' => $registrationType,
                'registration_id' => $registrationId,
                'amount' => $amount,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array{registration_type: class-string<Model>, registration_id: int, amount: int}  $allocation
     */
    private function registrationFromAllocation(array $allocation): Model
    {
        /** @var class-string<Model> $registrationType */
        $registrationType = $allocation['registration_type'];

        return $registrationType::query()->findOrFail($allocation['registration_id']);
    }

    private function syncRegistrationStatus(Model $registration): void
    {
        $expected = $this->expectedAmountFor($registration);

        if ($expected === null || $this->registrationIsCancelled($registration)) {
            return;
        }

        $paid = $this->paidAmountFor($registration);

        if ($registration instanceof Campista) {
            if ($paid >= $expected) {
                $latestPayment = $this->latestPaidLancamentoFor($registration);

                $registration->forceFill([
                    'status' => StatusInscricao::Pago,
                    'forma_pagamento' => $latestPayment?->forma_pagamento,
                    'dia_pagamento' => $latestPayment?->data,
                ])->save();

                return;
            }

            $registration->forceFill([
                'status' => StatusInscricao::Pendente,
                'forma_pagamento' => null,
                'dia_pagamento' => null,
            ])->save();

            return;
        }

        if ($registration instanceof EquipeTrabalho) {
            $registration->forceFill([
                'status' => $paid >= $expected
                    ? StatusInscricaoEquipeTrabalho::Aprovado
                    : StatusInscricaoEquipeTrabalho::Pendente,
            ])->save();
        }
    }

    private function latestPaidLancamentoFor(Model $registration): ?Lancamento
    {
        return Lancamento::query()
            ->whereHas('registrationPayments', fn ($query) => $query
                ->where('registration_type', $registration::class)
                ->where('registration_id', $registration->getKey()))
            ->where('status', StatusLacamento::Pago->value)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->first();
    }

    private function registrationOptionLabel(Model $registration, ?int $excludingLancamentoId = null): string
    {
        $expected = $this->expectedAmountFor($registration) ?? 0;
        $paid = $this->paidAmountFor($registration, $excludingLancamentoId);
        $remaining = max(0, $expected - $paid);

        return sprintf(
            '%s #%s - %s | valor %s | pago %s | saldo %s',
            $registration instanceof Campista ? 'Campista' : 'Equipe',
            $registration->getKey(),
            $this->registrationName($registration),
            $this->money($expected),
            $this->money($paid),
            $this->money($remaining),
        );
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return array<int, string>
     */
    private function registrationOptionResults(string $registrationType, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null, ?string $search = null, ?int $limit = null): array
    {
        return $registrationType::query()
            ->when(filled($search), fn ($query) => $query->where('nome', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $search)).'%'))
            ->when(
                filled($search),
                fn ($query) => $query->orderBy('nome')->orderBy('id'),
                fn ($query) => $query->orderBy('id'),
            )
            ->when($limit !== null, fn ($query) => $query->limit($limit * 4))
            ->get()
            ->filter(fn (Model $registration): bool => $this->registrationCanReceivePayment(
                registration: $registration,
                excludingLancamentoId: $excludingLancamentoId,
                currentRegistrationId: $currentRegistrationId,
            ))
            ->take($limit ?? PHP_INT_MAX)
            ->mapWithKeys(fn (Model $registration): array => [
                $registration->getKey() => $this->registrationOptionLabel($registration, $excludingLancamentoId),
            ])
            ->all();
    }

    private function registrationCanReceivePayment(Model $registration, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null): bool
    {
        if ($this->registrationIsCancelled($registration)) {
            return false;
        }

        return $registration->getKey() === $currentRegistrationId
            || $this->remainingAmountFor($registration, $excludingLancamentoId) > 0;
    }

    private function registrationName(Model $registration): string
    {
        return (string) ($registration->getAttribute('nome') ?? 'Inscrição #'.$registration->getKey());
    }

    private function registrationIsCancelled(Model $registration): bool
    {
        return match (true) {
            $registration instanceof Campista => $registration->status === StatusInscricao::Cancelado,
            $registration instanceof EquipeTrabalho => $registration->status === StatusInscricaoEquipeTrabalho::Cancelado,
            default => true,
        };
    }

    private function isSupportedRegistrationType(mixed $registrationType): bool
    {
        return is_string($registrationType)
            && array_key_exists($registrationType, self::registrationTypeOptions());
    }

    private function money(int $amount): string
    {
        return 'R$ '.number_format($amount / 100, 2, ',', '.');
    }
}
