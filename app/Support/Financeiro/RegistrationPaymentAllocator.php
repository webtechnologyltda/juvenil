<?php

namespace App\Support\Financeiro;

use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\StatusLacamento;
use App\Enums\TipoEquipeTrabalho;
use App\Enums\TipoLacamento;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Builder;
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
    public function registrationOptions(?string $registrationType, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null, ?int $categoryId = null): array
    {
        if (! $this->isSupportedRegistrationType($registrationType)) {
            return [];
        }

        /** @var class-string<Model> $registrationType */
        return $this->registrationOptionResults(
            registrationType: $registrationType,
            excludingLancamentoId: $excludingLancamentoId,
            currentRegistrationId: $currentRegistrationId,
            categoryId: $categoryId,
        );
    }

    /**
     * @return array<int, string>
     */
    public function registrationSearchResults(?string $registrationType, ?string $search, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null, ?int $categoryId = null): array
    {
        if (! $this->isSupportedRegistrationType($registrationType) || blank($search)) {
            return [];
        }

        /** @var class-string<Model> $registrationType */
        return $this->registrationOptionResults(
            registrationType: $registrationType,
            excludingLancamentoId: $excludingLancamentoId,
            currentRegistrationId: $currentRegistrationId,
            categoryId: $categoryId,
            search: $search,
            limit: self::REGISTRATION_SEARCH_LIMIT,
        );
    }

    /**
     * @param  array<int, mixed>  $registrationIds
     * @return array<int, string>
     */
    public function registrationOptionLabels(?string $registrationType, array $registrationIds, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null, ?int $categoryId = null): array
    {
        if (! $this->isSupportedRegistrationType($registrationType)) {
            return [];
        }

        $registrationIds = $this->normalizeRegistrationIds($registrationIds);

        if ($registrationIds === []) {
            return [];
        }

        /** @var class-string<Model> $registrationType */
        return $this->registrationOptionResults(
            registrationType: $registrationType,
            excludingLancamentoId: $excludingLancamentoId,
            currentRegistrationId: $currentRegistrationId,
            categoryId: $categoryId,
            registrationIds: $registrationIds,
        );
    }

    /**
     * @param  array<int, mixed>  $registrationIds
     * @return array<int, int>
     */
    public function eligibleRegistrationIds(?string $registrationType, array $registrationIds, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null, ?int $categoryId = null): array
    {
        if (! $this->isSupportedRegistrationType($registrationType)) {
            return [];
        }

        $registrationIds = $this->normalizeRegistrationIds($registrationIds);

        if ($registrationIds === []) {
            return [];
        }

        /** @var class-string<Model> $registrationType */
        $query = $this->applyPaymentEligibilityQuery(
            query: $registrationType::query()->whereKey($registrationIds),
            registrationType: $registrationType,
            excludingLancamentoId: $excludingLancamentoId,
            currentRegistrationId: $currentRegistrationId,
            categoryId: $categoryId,
        );

        return $query
            ->pluck($query->getModel()->getKeyName())
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{nome: string, descricao: ?string, valor: int, categoria_lancamento_id: int, registration_type: class-string<Model>|null, registration_id: int|null}>
     */
    public function validateItems(Lancamento $lancamento, array $items): array
    {
        $normalized = $this->normalizeItems($items);

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'items' => 'Informe pelo menos um item do lançamento.',
            ]);
        }

        $seenRegistrations = [];

        foreach ($normalized as $item) {
            $category = CategoriaLancamento::query()->find($item['categoria_lancamento_id']);

            if (! $category || ! $this->categoryMatchesLaunchType($category, $lancamento->tipo)) {
                throw ValidationException::withMessages([
                    'items' => 'A categoria do item precisa acompanhar o tipo do lançamento.',
                ]);
            }

            if ($item['registration_type'] === null || $item['registration_id'] === null) {
                continue;
            }

            $key = $item['registration_type'].'#'.$item['registration_id'].'#'.$item['categoria_lancamento_id'];

            if (array_key_exists($key, $seenRegistrations)) {
                throw ValidationException::withMessages([
                    'items' => 'A mesma inscrição não pode ser vinculada duas vezes à mesma categoria no lançamento.',
                ]);
            }

            $seenRegistrations[$key] = true;
            $registration = $this->registrationFromItem($item);

            if ($this->registrationIsCancelled($registration)) {
                throw ValidationException::withMessages([
                    'items' => sprintf('%s está com inscrição cancelada.', $this->registrationName($registration)),
                ]);
            }

            if (! $this->itemCategoryCountsTowardRegistrationPayment($item['registration_type'], $item['categoria_lancamento_id'])) {
                continue;
            }

            $excludingLancamentoId = $lancamento->exists ? (int) $lancamento->getKey() : null;
            $expected = $this->expectedAmountFor($registration);

            if ($expected === null) {
                throw ValidationException::withMessages([
                    'items' => $this->missingConfiguredAmountMessageFor($registration),
                ]);
            }

            if ($this->registrationHasPaymentLinkedItem($registration, $excludingLancamentoId)) {
                throw ValidationException::withMessages([
                    'items' => sprintf('%s já possui lançamento vinculado.', $this->registrationName($registration)),
                ]);
            }

            $remaining = max(0, $expected - $this->paidAmountFor($registration, $excludingLancamentoId));

            if ($item['valor'] > $remaining) {
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'O valor aplicado em %s não pode ultrapassar o saldo restante da inscrição.',
                        $this->registrationName($registration),
                    ),
                ]);
            }
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function syncItems(Lancamento $lancamento, array $items): void
    {
        $normalized = $this->validateItems($lancamento, $items);

        DB::transaction(function () use ($lancamento, $normalized): void {
            $previousRegistrations = $lancamento->items()
                ->with('registration')
                ->get()
                ->map(fn (LancamentoItem $item): ?Model => $item->registration)
                ->filter();

            $lancamento->items()->delete();

            foreach ($normalized as $item) {
                $lancamento->items()->create($item);
            }

            $currentRegistrations = collect($normalized)
                ->filter(fn (array $item): bool => $item['registration_type'] !== null && $item['registration_id'] !== null)
                ->map(fn (array $item): Model => $this->registrationFromItem($item));

            $lancamento->forceFill([
                'valor' => $this->signedTotalForItems($lancamento->tipo, $normalized),
            ])->save();

            $previousRegistrations
                ->merge($currentRegistrations)
                ->unique(fn (Model $registration): string => $registration::class.'#'.$registration->getKey())
                ->each(fn (Model $registration): mixed => $this->syncRegistrationStatus($registration));
        });
    }

    /**
     * Compatibility wrapper for older call sites while forms move to item-based payloads.
     *
     * @param  array<int, array<string, mixed>>  $allocations
     */
    public function sync(Lancamento $lancamento, array $allocations): void
    {
        CategoriaLancamento::ensureSystemDefaults();

        $items = collect($this->normalizeAllocations($allocations))
            ->map(function (array $allocation): array {
                $registration = $this->registrationFromAllocation($allocation);

                return [
                    'nome' => $this->registrationName($registration),
                    'descricao' => null,
                    'valor' => $allocation['amount'],
                    'categoria_lancamento_id' => $this->categoryForRegistrationType($allocation['registration_type'])?->id,
                    'registration_type' => $allocation['registration_type'],
                    'registration_id' => $allocation['registration_id'],
                ];
            })
            ->all();

        $this->syncItems($lancamento, $items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function signedTotalForItems(mixed $type, array $items): int
    {
        $total = collect($items)
            ->sum(fn (array $item): int => abs((int) ($item['valor'] ?? $item['amount'] ?? 0)));

        return $this->isExpenseType($type) ? $total * -1 : $total;
    }

    public function paidAmountFor(Model $registration, ?int $excludingLancamentoId = null): int
    {
        $query = LancamentoItem::query()
            ->where('registration_type', $registration::class)
            ->where('registration_id', $registration->getKey())
            ->where('categoria_lancamento_id', $this->categoryForRegistrationType($registration::class)?->id)
            ->whereHas('lancamento', fn ($query) => $query->where('status', StatusLacamento::Pago->value));

        if ($excludingLancamentoId !== null) {
            $query->where('lancamento_id', '<>', $excludingLancamentoId);
        }

        return (int) $query->sum('valor');
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model>  $registrationType
     * @return Builder<Model>
     */
    public function applyPaymentEligibilityQuery(Builder $query, string $registrationType, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null, ?int $categoryId = null): Builder
    {
        if (! $this->isSupportedRegistrationType($registrationType)) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($this->registrationStatusColumn(), '<>', $this->cancelledStatusValue($registrationType));

        if (! $this->categoryCountsTowardRegistrationPayment($registrationType, $categoryId)) {
            return $query;
        }

        $paymentCategoryId = $this->categoryForRegistrationType($registrationType)?->id;

        if ($paymentCategoryId === null) {
            return $currentRegistrationId !== null
                ? $query->whereKey($currentRegistrationId)
                : $query->whereRaw('1 = 0');
        }

        $table = $query->getModel()->getTable();
        $linkedSql = <<<'SQL'
            exists (
                select 1
                from lancamento_items as linked_items
                where linked_items.registration_type = ?
                    and linked_items.registration_id = %s.id
                    and linked_items.categoria_lancamento_id = ?
                    %s
            )
        SQL;
        $paidSql = <<<'SQL'
            (
                select coalesce(sum(paid_items.valor), 0)
                from lancamento_items as paid_items
                inner join lancamentos as paid_lancamentos on paid_lancamentos.id = paid_items.lancamento_id
                where paid_items.registration_type = ?
                    and paid_items.registration_id = %s.id
                    and paid_items.categoria_lancamento_id = ?
                    and paid_lancamentos.status = ?
                    %s
            )
        SQL;

        $excludingSql = $excludingLancamentoId !== null ? 'and %s.lancamento_id <> ?' : '';
        $linkedBindings = [$registrationType, $paymentCategoryId];
        $paidBindings = [$registrationType, $paymentCategoryId, StatusLacamento::Pago->value];

        if ($excludingLancamentoId !== null) {
            $linkedBindings[] = $excludingLancamentoId;
            $paidBindings[] = $excludingLancamentoId;
        }

        $linkedSql = sprintf($linkedSql, $table, $excludingSql === '' ? '' : sprintf($excludingSql, 'linked_items'));
        $paidSql = sprintf($paidSql, $table, $excludingSql === '' ? '' : sprintf($excludingSql, 'paid_items'));
        $expectedSql = $this->expectedAmountSqlFor($registrationType);

        return $query->where(function (Builder $query) use ($currentRegistrationId, $linkedSql, $linkedBindings, $paidSql, $paidBindings, $expectedSql): void {
            if ($currentRegistrationId !== null) {
                $query->whereKey($currentRegistrationId);
            }

            $query->orWhere(function (Builder $query) use ($linkedSql, $linkedBindings, $paidSql, $paidBindings, $expectedSql): void {
                $query
                    ->whereRaw("not {$linkedSql}", $linkedBindings)
                    ->whereRaw("{$paidSql} < {$expectedSql}", $paidBindings);
            });
        });
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

        $amount = $this->configuredAmountFor($registration);

        return $amount > 0 ? $amount : null;
    }

    public function missingConfiguredAmountMessageFor(Model $registration): string
    {
        if ($registration instanceof EquipeTrabalho) {
            return sprintf(
                'Configure um valor maior que zero para equipe de trabalho %s antes de vincular o lançamento.',
                mb_strtolower((string) $this->teamTypeFor($registration)->getLabel()),
            );
        }

        return 'Configure um valor de acampamento maior que zero antes de vincular o lançamento.';
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{nome: string, descricao: ?string, valor: int, categoria_lancamento_id: int, registration_type: class-string<Model>|null, registration_id: int|null}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $name = trim((string) ($item['nome'] ?? ''));
            $description = $item['descricao'] ?? null;
            $amount = abs((int) ($item['valor'] ?? $item['amount'] ?? 0));
            $categoryId = (int) ($item['categoria_lancamento_id'] ?? 0);
            $registrationType = $item['registration_type'] ?? null;
            $registrationId = filled($item['registration_id'] ?? null) ? (int) $item['registration_id'] : null;

            if ($name === '' && $amount === 0 && $categoryId === 0 && blank($registrationType) && $registrationId === null) {
                continue;
            }

            if ($name === '' || $amount <= 0 || $categoryId <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Informe nome, valor e categoria em todos os itens.',
                ]);
            }

            if (filled($registrationType) || $registrationId !== null) {
                if (! $this->isSupportedRegistrationType($registrationType) || $registrationId === null || $registrationId <= 0) {
                    throw ValidationException::withMessages([
                        'items' => 'Informe tipo e inscrição válidos para itens vinculados.',
                    ]);
                }
            } else {
                $registrationType = null;
                $registrationId = null;
            }

            $normalized[] = [
                'nome' => $name,
                'descricao' => is_string($description) && trim($description) !== '' ? trim($description) : null,
                'valor' => $amount,
                'categoria_lancamento_id' => $categoryId,
                'registration_type' => $registrationType,
                'registration_id' => $registrationId,
            ];
        }

        return $normalized;
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

    /**
     * @param  array{registration_type: class-string<Model>|null, registration_id: int|null}  $item
     */
    private function registrationFromItem(array $item): Model
    {
        /** @var class-string<Model> $registrationType */
        $registrationType = $item['registration_type'];

        return $registrationType::query()->findOrFail($item['registration_id']);
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
            ->whereHas('items', fn ($query) => $query
                ->where('registration_type', $registration::class)
                ->where('registration_id', $registration->getKey())
                ->where('categoria_lancamento_id', $this->categoryForRegistrationType($registration::class)?->id))
            ->where('status', StatusLacamento::Pago->value)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->first();
    }

    private function registrationOptionLabel(Model $registration, ?int $excludingLancamentoId = null, ?int $paidAmount = null): string
    {
        $expected = $this->expectedAmountFor($registration) ?? 0;
        $paid = $paidAmount ?? $this->paidAmountFor($registration, $excludingLancamentoId);
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
    private function registrationOptionResults(string $registrationType, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null, ?int $categoryId = null, ?string $search = null, ?int $limit = null, ?array $registrationIds = null): array
    {
        $query = $registrationType::query();

        $registrations = $this->applyPaymentEligibilityQuery(
            query: $query,
            registrationType: $registrationType,
            excludingLancamentoId: $excludingLancamentoId,
            currentRegistrationId: $currentRegistrationId,
            categoryId: $categoryId,
        )
            ->select($this->registrationOptionColumns($registrationType))
            ->when($registrationIds !== null, fn (Builder $query): Builder => $query->whereKey($registrationIds))
            ->when(filled($search), fn ($query) => $query->where('nome', 'like', '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $search)).'%'))
            ->when(
                filled($search),
                fn ($query) => $query->orderBy('nome')->orderBy('id'),
                fn ($query) => $query->orderBy('id'),
            )
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get();

        $paidAmounts = $this->paidAmountsFor(
            registrationType: $registrationType,
            registrationIds: $registrations->modelKeys(),
            excludingLancamentoId: $excludingLancamentoId,
        );

        return $registrations
            ->mapWithKeys(fn (Model $registration): array => [
                $registration->getKey() => $this->registrationOptionLabel(
                    registration: $registration,
                    excludingLancamentoId: $excludingLancamentoId,
                    paidAmount: $paidAmounts[$registration->getKey()] ?? 0,
                ),
            ])
            ->all();
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return array<int, string>
     */
    private function registrationOptionColumns(string $registrationType): array
    {
        return $registrationType === EquipeTrabalho::class
            ? ['id', 'nome', 'tipo_equipe']
            : ['id', 'nome'];
    }

    /**
     * @param  array<int, mixed>  $registrationIds
     * @return array<int, int>
     */
    private function normalizeRegistrationIds(array $registrationIds): array
    {
        return collect($registrationIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @param  array<int, int|string>  $registrationIds
     * @return array<int, int>
     */
    private function paidAmountsFor(string $registrationType, array $registrationIds, ?int $excludingLancamentoId = null): array
    {
        if ($registrationIds === []) {
            return [];
        }

        $categoryId = $this->categoryForRegistrationType($registrationType)?->id;

        if ($categoryId === null) {
            return [];
        }

        return LancamentoItem::query()
            ->select('registration_id')
            ->selectRaw('coalesce(sum(valor), 0) as paid_amount')
            ->where('registration_type', $registrationType)
            ->whereIn('registration_id', $registrationIds)
            ->where('categoria_lancamento_id', $categoryId)
            ->whereHas('lancamento', fn (Builder $query): Builder => $query->where('status', StatusLacamento::Pago->value))
            ->when($excludingLancamentoId !== null, fn (Builder $query): Builder => $query->where('lancamento_id', '<>', $excludingLancamentoId))
            ->groupBy('registration_id')
            ->pluck('paid_amount', 'registration_id')
            ->map(fn (mixed $amount): int => (int) $amount)
            ->all();
    }

    private function registrationCanReceivePayment(Model $registration, ?int $excludingLancamentoId = null, ?int $currentRegistrationId = null, ?int $categoryId = null): bool
    {
        if ($this->registrationIsCancelled($registration)) {
            return false;
        }

        if ($registration->getKey() === $currentRegistrationId) {
            return true;
        }

        if (! $this->categoryCountsTowardRegistrationPayment($registration::class, $categoryId)) {
            return true;
        }

        return ! $this->registrationHasPaymentLinkedItem($registration, $excludingLancamentoId)
            && $this->remainingAmountFor($registration, $excludingLancamentoId) > 0;
    }

    private function registrationHasPaymentLinkedItem(Model $registration, ?int $excludingLancamentoId = null): bool
    {
        return LancamentoItem::query()
            ->where('registration_type', $registration::class)
            ->where('registration_id', $registration->getKey())
            ->where('categoria_lancamento_id', $this->categoryForRegistrationType($registration::class)?->id)
            ->when($excludingLancamentoId !== null, fn ($query) => $query->where('lancamento_id', '<>', $excludingLancamentoId))
            ->exists();
    }

    private function itemCategoryCountsTowardRegistrationPayment(string $registrationType, int $categoryId): bool
    {
        return $this->categoryCountsTowardRegistrationPayment($registrationType, $categoryId);
    }

    private function categoryCountsTowardRegistrationPayment(string $registrationType, ?int $categoryId): bool
    {
        if ($categoryId === null) {
            return true;
        }

        return $this->categoryForRegistrationType($registrationType)?->id === $categoryId;
    }

    public function categoryForRegistrationType(string $registrationType): ?CategoriaLancamento
    {
        $systemKey = match ($registrationType) {
            Campista::class => CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO,
            EquipeTrabalho::class => CategoriaLancamento::SYSTEM_CATEGORY_CONTRIBUICAO_EQUIPE_TRABALHO,
            default => null,
        };

        if ($systemKey === null) {
            return null;
        }

        CategoriaLancamento::ensureSystemDefaults();

        return CategoriaLancamento::query()
            ->where('system_key', $systemKey)
            ->first();
    }

    private function categoryMatchesLaunchType(CategoriaLancamento $category, mixed $type): bool
    {
        if ($type instanceof TipoLacamento) {
            return $category->tipo === $type;
        }

        return ! blank($type) && (int) $category->tipo->value === (int) $type;
    }

    private function isExpenseType(mixed $type): bool
    {
        if ($type instanceof TipoLacamento) {
            return $type === TipoLacamento::Despesa;
        }

        return ! blank($type) && (int) $type === TipoLacamento::Despesa->value;
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

    private function registrationStatusColumn(): string
    {
        return 'status';
    }

    /**
     * @param  class-string<Model>  $registrationType
     */
    private function cancelledStatusValue(string $registrationType): int
    {
        return match ($registrationType) {
            Campista::class => StatusInscricao::Cancelado->value,
            EquipeTrabalho::class => StatusInscricaoEquipeTrabalho::Cancelado->value,
            default => -1,
        };
    }

    /**
     * @param  class-string<Model>  $registrationType
     */
    private function expectedAmountSqlFor(string $registrationType): string
    {
        $settings = app(GeneralSettings::class);

        if ($registrationType === Campista::class) {
            return (string) max(0, (int) ($settings->valor_acampamento ?? 0));
        }

        if ($registrationType === EquipeTrabalho::class) {
            $internal = max(0, (int) ($settings->valor_equipe_trabalho_interna ?? 0));
            $external = max(0, (int) ($settings->valor_equipe_trabalho_externa ?? 0));

            return sprintf(
                '(case when tipo_equipe = %d then %d when tipo_equipe = %d then %d else 0 end)',
                TipoEquipeTrabalho::Interna->value,
                $internal,
                TipoEquipeTrabalho::Externa->value,
                $external,
            );
        }

        return '0';
    }

    private function isSupportedRegistrationType(mixed $registrationType): bool
    {
        return is_string($registrationType)
            && array_key_exists($registrationType, self::registrationTypeOptions());
    }

    private function configuredAmountFor(Model $registration): int
    {
        $settings = app(GeneralSettings::class);

        if ($registration instanceof Campista) {
            return (int) ($settings->valor_acampamento ?? 0);
        }

        if ($registration instanceof EquipeTrabalho) {
            $field = $this->teamTypeFor($registration)->configuredAmountField();

            return (int) ($settings->{$field} ?? 0);
        }

        return 0;
    }

    private function teamTypeFor(EquipeTrabalho $registration): TipoEquipeTrabalho
    {
        if ($registration->tipo_equipe instanceof TipoEquipeTrabalho) {
            return $registration->tipo_equipe;
        }

        return TipoEquipeTrabalho::tryFrom((int) ($registration->tipo_equipe ?? TipoEquipeTrabalho::Interna->value))
            ?? TipoEquipeTrabalho::Interna;
    }

    private function money(int $amount): string
    {
        return 'R$ '.number_format($amount / 100, 2, ',', '.');
    }
}
