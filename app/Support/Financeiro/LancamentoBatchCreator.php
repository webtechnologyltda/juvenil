<?php

namespace App\Support\Financeiro;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Settings\GeneralSettings;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LancamentoBatchCreator
{
    public const MODE_REGISTRATIONS = 'registrations';

    public const MODE_MANUAL = 'manual';

    public function __construct(
        private readonly RegistrationPaymentAllocator $allocator,
    ) {}

    public function nextBatchCode(?CarbonInterface $date = null): string
    {
        $prefix = 'LOTE-'.($date ?? now())->format('Ymd');

        $lastSequence = Lancamento::query()
            ->where('batch_code', 'like', $prefix.'-%')
            ->pluck('batch_code')
            ->map(fn (?string $code): int => (int) substr((string) $code, -3))
            ->max() ?? 0;

        return sprintf('%s-%03d', $prefix, $lastSequence + 1);
    }

    /**
     * @return array<int, string>
     */
    public function registrationOptions(?string $registrationType, ?string $search = null): array
    {
        return filled($search)
            ? $this->allocator->registrationSearchResults($registrationType, $search)
            : $this->allocator->registrationOptions($registrationType);
    }

    /**
     * @return Collection<int, Lancamento>
     */
    public function create(array $data): Collection
    {
        return DB::transaction(function () use ($data): Collection {
            $mode = $data['mode'] ?? self::MODE_REGISTRATIONS;
            $batchCode = $this->nextBatchCode();

            return match ($mode) {
                self::MODE_MANUAL => $this->createManualBatch($data, $batchCode),
                default => $this->createRegistrationBatch($data, $batchCode),
            };
        });
    }

    /**
     * @return Collection<int, Lancamento>
     */
    private function createRegistrationBatch(array $data, string $batchCode): Collection
    {
        $registrationType = $data['registration_type'] ?? Campista::class;

        if (! array_key_exists($registrationType, RegistrationPaymentAllocator::registrationTypeOptions())) {
            throw ValidationException::withMessages([
                'registration_type' => 'Selecione um tipo de inscrição válido.',
            ]);
        }

        /** @var class-string<Model> $registrationType */
        $rows = $this->registrationRows($data, $registrationType);

        if ($rows === []) {
            throw ValidationException::withMessages([
                'registration_ids' => 'Selecione pelo menos uma inscrição para o lote.',
            ]);
        }

        $category = $this->allocator->categoryForRegistrationType($registrationType);

        if (! $category) {
            throw ValidationException::withMessages([
                'registration_type' => 'A categoria padrão desse tipo de inscrição não foi encontrada.',
            ]);
        }

        $availableIds = array_keys($this->registrationOptions($registrationType));
        $selectedIds = collect($rows)->pluck('registration_id')->unique()->values()->all();
        $invalidIds = array_values(array_diff($selectedIds, $availableIds));

        if ($invalidIds !== []) {
            throw ValidationException::withMessages([
                'registration_ids' => 'Remova inscrições canceladas ou já vinculadas antes de criar o lote.',
            ]);
        }

        return collect($rows)->map(function (array $row) use ($registrationType, $category, $data, $batchCode): Lancamento {
            $registration = $registrationType::query()->findOrFail($row['registration_id']);
            $name = $this->rowName($row, $registration);

            $lancamento = Lancamento::query()->create([
                'nome' => $this->registrationLaunchName($registration, $name),
                'descricao' => $this->nullableText($row['descricao'] ?? $data['descricao'] ?? null),
                'comprador' => null,
                'data' => $data['data'] ?? now()->toDateString(),
                'valor' => 0,
                'tipo' => TipoLacamento::Receita,
                'status' => StatusLacamento::Pendente,
                'forma_pagamento' => FormaPagamento::NaoPago,
                'comprovante' => [],
                'batch_code' => $batchCode,
                'user_id' => auth()->id(),
            ]);

            $this->allocator->syncItems($lancamento, [[
                'nome' => $name,
                'descricao' => $this->nullableText($row['descricao'] ?? null),
                'valor' => $this->amount($row['valor'] ?? $data['default_value'] ?? $this->defaultRegistrationAmount()),
                'categoria_lancamento_id' => $category->id,
                'registration_type' => $registrationType,
                'registration_id' => $registration->getKey(),
            ]]);

            return $lancamento->fresh(['items']);
        });
    }

    /**
     * @return Collection<int, Lancamento>
     */
    private function createManualBatch(array $data, string $batchCode): Collection
    {
        $type = TipoLacamento::from((int) ($data['tipo'] ?? TipoLacamento::Receita->value));
        $items = $this->manualRows($data);

        if ($items === []) {
            throw ValidationException::withMessages([
                'manual_items' => 'Informe pelo menos um item avulso para o lote.',
            ]);
        }

        return collect($items)->map(function (array $item) use ($type, $data, $batchCode): Lancamento {
            $lancamento = Lancamento::query()->create([
                'nome' => $this->nullableText($item['nome'] ?? null) ?? 'Lançamento avulso',
                'descricao' => $this->nullableText($item['descricao'] ?? $data['descricao'] ?? null),
                'comprador' => $type === TipoLacamento::Despesa ? $this->nullableText($item['comprador'] ?? $data['comprador'] ?? 'Lote financeiro') : null,
                'data' => $data['data'] ?? now()->toDateString(),
                'valor' => 0,
                'tipo' => $type,
                'status' => StatusLacamento::Pendente,
                'forma_pagamento' => FormaPagamento::NaoPago,
                'comprovante' => [],
                'batch_code' => $batchCode,
                'user_id' => auth()->id(),
            ]);

            $this->allocator->syncItems($lancamento, [[
                'nome' => $lancamento->nome,
                'descricao' => $this->nullableText($item['descricao'] ?? null),
                'valor' => $this->amount($item['valor'] ?? $data['default_value'] ?? 0),
                'categoria_lancamento_id' => (int) ($item['categoria_lancamento_id'] ?? $data['categoria_lancamento_id'] ?? 0),
                'registration_type' => null,
                'registration_id' => null,
            ]]);

            return $lancamento->fresh(['items']);
        });
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return array<int, array{registration_id: int, nome: ?string, valor: int, descricao: ?string}>
     */
    private function registrationRows(array $data, string $registrationType): array
    {
        $selectedIds = collect($data['registration_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $rows = collect($data['registration_items'] ?? [])
            ->map(function (array $row) use ($data): array {
                return [
                    'registration_id' => (int) ($row['registration_id'] ?? 0),
                    'nome' => $this->nullableText($row['nome'] ?? null),
                    'valor' => $this->amount($row['valor'] ?? $data['default_value'] ?? $this->defaultRegistrationAmount()),
                    'descricao' => $this->nullableText($row['descricao'] ?? null),
                ];
            })
            ->filter(fn (array $row): bool => $row['registration_id'] > 0)
            ->values();

        if ($rows->isEmpty()) {
            $rows = $selectedIds->map(fn (int $id): array => [
                'registration_id' => $id,
                'nome' => null,
                'valor' => $this->amount($data['default_value'] ?? $this->defaultRegistrationAmount()),
                'descricao' => null,
            ]);
        }

        $duplicates = $rows
            ->pluck('registration_id')
            ->duplicates()
            ->all();

        if ($duplicates !== []) {
            throw ValidationException::withMessages([
                'registration_ids' => 'A mesma inscrição não pode aparecer duas vezes no lote.',
            ]);
        }

        if ($selectedIds->isNotEmpty()) {
            $rows = $rows->filter(fn (array $row): bool => $selectedIds->contains($row['registration_id']));
        }

        return $rows->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function manualRows(array $data): array
    {
        return collect($data['manual_items'] ?? [])
            ->map(fn (array $row): array => [
                'nome' => $this->nullableText($row['nome'] ?? null),
                'valor' => $this->amount($row['valor'] ?? $data['default_value'] ?? 0),
                'categoria_lancamento_id' => (int) ($row['categoria_lancamento_id'] ?? $data['categoria_lancamento_id'] ?? 0),
                'descricao' => $this->nullableText($row['descricao'] ?? null),
                'comprador' => $this->nullableText($row['comprador'] ?? null),
            ])
            ->filter(fn (array $row): bool => filled($row['nome']) || $row['valor'] > 0 || $row['categoria_lancamento_id'] > 0)
            ->values()
            ->all();
    }

    private function rowName(array $row, Model $registration): string
    {
        return $this->nullableText($row['nome'] ?? null)
            ?? (string) ($registration->getAttribute('nome') ?? 'Inscrição #'.$registration->getKey());
    }

    private function registrationLaunchName(Model $registration, string $name): string
    {
        $prefix = $registration instanceof EquipeTrabalho
            ? 'Contribuição equipe'
            : 'Inscrição';

        return $prefix.' - '.$name;
    }

    private function defaultRegistrationAmount(): int
    {
        return (int) (app(GeneralSettings::class)->valor_acampamento ?? 0);
    }

    private function amount(mixed $value): int
    {
        return MoneyAmount::toCents($value);
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
