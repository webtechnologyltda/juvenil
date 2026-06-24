<?php

namespace App\Support\Financeiro;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class AutomaticRegistrationLaunchService
{
    public const TYPE_CAMPISTA = 'campista';

    public const TYPE_EQUIPE = 'equipe';

    public const ACTION_CREATED = 'created';

    public const ACTION_REACTIVATED = 'reactivated';

    public const ACTION_CANCELLED = 'cancelled';

    public const ACTION_SKIPPED = 'skipped';

    public function __construct(
        private readonly RegistrationPaymentAllocator $allocator,
        private readonly LancamentoBatchCreator $batchCreator,
    ) {}

    /**
     * @return array<string, class-string<Model>>
     */
    public static function registrationTypes(): array
    {
        return [
            self::TYPE_CAMPISTA => Campista::class,
            self::TYPE_EQUIPE => EquipeTrabalho::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_CAMPISTA => 'Campistas',
            self::TYPE_EQUIPE => 'Equipe de trabalho',
        ];
    }

    /**
     * @return array<string, array{candidates: int, created: int, reactivated: int, cancelled: int, skipped: int, failed: int}>
     */
    public function preview(?string $type = null): array
    {
        $summary = [];

        foreach ($this->typesFor($type) as $typeKey => $registrationType) {
            $summary[$typeKey] = $this->previewType($registrationType);
        }

        return $summary;
    }

    /**
     * @return array<string, array{candidates: int, created: int, reactivated: int, cancelled: int, skipped: int, failed: int}>
     */
    public function reconcile(?string $type = null): array
    {
        $summary = [];

        foreach ($this->typesFor($type) as $typeKey => $registrationType) {
            $summary[$typeKey] = $this->reconcileType($registrationType);
        }

        return $summary;
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return array{action: string, reason: ?string, registration_type: class-string<Model>, registration_id: int, lancamento_id: int|null}
     */
    public function ensureForRegistration(
        string $registrationType,
        int $registrationId,
        string $originContext = Lancamento::ORIGIN_CONTEXT_OBSERVER,
        ?string $batchCode = null,
        ?string $description = null,
    ): array {
        $this->ensureSupportedRegistrationType($registrationType);

        return DB::transaction(function () use ($registrationType, $registrationId, $originContext, $batchCode, $description): array {
            $registration = $this->findRegistration($registrationType, $registrationId, lock: true);

            if (! $registration) {
                return $this->result(self::ACTION_SKIPPED, 'registration_not_found', $registrationType, $registrationId);
            }

            if (! $this->registrationIsPending($registration)) {
                return $this->result(self::ACTION_SKIPPED, 'registration_not_pending', $registrationType, $registrationId);
            }

            $linkedItems = $this->linkedItemsFor($registration);
            $reactivatableLaunch = $this->singleAutomaticLaunch($linkedItems, StatusLacamento::Cancelado);

            if ($reactivatableLaunch) {
                $reactivatableLaunch->forceFill([
                    'status' => StatusLacamento::Pendente,
                    'forma_pagamento' => FormaPagamento::NaoPago,
                    'origin_context' => $originContext,
                ])->save();

                return $this->result(
                    self::ACTION_REACTIVATED,
                    null,
                    $registrationType,
                    $registrationId,
                    (int) $reactivatableLaunch->getKey(),
                );
            }

            if ($linkedItems->isNotEmpty()) {
                return $this->result(self::ACTION_SKIPPED, 'registration_already_linked', $registrationType, $registrationId);
            }

            $amount = $this->expectedAmountFor($registration);
            $category = $this->categoryForRegistrationType($registrationType);
            $registrationName = $this->registrationName($registration);

            $lancamento = Lancamento::query()->create([
                'nome' => $this->launchName($registration, $registrationName),
                'descricao' => $description ?? $this->descriptionForContext($originContext),
                'comprador' => null,
                'data' => now()->toDateString(),
                'valor' => $amount,
                'tipo' => TipoLacamento::Receita,
                'status' => StatusLacamento::Pendente,
                'forma_pagamento' => FormaPagamento::NaoPago,
                'comprovante' => [],
                'batch_code' => $batchCode,
                'user_id' => null,
                'origin' => Lancamento::ORIGIN_AUTO_REGISTRATION,
                'origin_context' => $originContext,
            ]);

            $lancamento->items()->create([
                'nome' => $registrationName,
                'descricao' => null,
                'valor' => $amount,
                'categoria_lancamento_id' => $category->id,
                'registration_type' => $registrationType,
                'registration_id' => $registrationId,
            ]);

            return $this->result(self::ACTION_CREATED, null, $registrationType, $registrationId, (int) $lancamento->getKey());
        });
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return array{action: string, reason: ?string, registration_type: class-string<Model>, registration_id: int, lancamento_id: int|null}
     */
    public function cancelForRegistration(string $registrationType, int $registrationId): array
    {
        $this->ensureSupportedRegistrationType($registrationType);

        return DB::transaction(function () use ($registrationType, $registrationId): array {
            $registration = $this->findRegistration($registrationType, $registrationId, lock: true);

            if (! $registration) {
                return $this->result(self::ACTION_SKIPPED, 'registration_not_found', $registrationType, $registrationId);
            }

            if (! $this->registrationIsCancelled($registration)) {
                return $this->result(self::ACTION_SKIPPED, 'registration_not_cancelled', $registrationType, $registrationId);
            }

            $lancamento = $this->singleAutomaticLaunch($this->linkedItemsFor($registration), StatusLacamento::Pendente);

            if (! $lancamento) {
                return $this->result(self::ACTION_SKIPPED, 'no_pending_automatic_launch', $registrationType, $registrationId);
            }

            $lancamento->forceFill([
                'status' => StatusLacamento::Cancelado,
            ])->save();

            return $this->result(self::ACTION_CANCELLED, null, $registrationType, $registrationId, (int) $lancamento->getKey());
        });
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return array{candidates: int, created: int, reactivated: int, cancelled: int, skipped: int, failed: int}
     */
    private function reconcileType(string $registrationType): array
    {
        $summary = $this->emptySummary();
        $batchCode = $this->hasPendingRegistrationWithoutLink($registrationType)
            ? $this->batchCreator->nextBatchCode()
            : null;

        $this->registrationsByStatus($registrationType, $this->pendingStatusFor($registrationType))
            ->chunkById(100, function (Collection $registrations) use (&$summary, $registrationType, $batchCode): void {
                foreach ($registrations as $registration) {
                    try {
                        $this->mergeResult($summary, $this->ensureForRegistration(
                            registrationType: $registrationType,
                            registrationId: (int) $registration->getKey(),
                            originContext: Lancamento::ORIGIN_CONTEXT_DAILY_RECONCILIATION,
                            batchCode: $batchCode,
                            description: 'Lançamento automático de regularização diária.',
                        ));
                    } catch (Throwable $exception) {
                        report($exception);
                        $summary['failed']++;
                    }
                }
            });

        $this->registrationsByStatus($registrationType, $this->cancelledStatusFor($registrationType))
            ->chunkById(100, function (Collection $registrations) use (&$summary, $registrationType): void {
                foreach ($registrations as $registration) {
                    try {
                        $this->mergeResult($summary, $this->cancelForRegistration(
                            registrationType: $registrationType,
                            registrationId: (int) $registration->getKey(),
                        ));
                    } catch (Throwable $exception) {
                        report($exception);
                        $summary['failed']++;
                    }
                }
            });

        return $summary;
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return array{candidates: int, created: int, reactivated: int, cancelled: int, skipped: int, failed: int}
     */
    private function previewType(string $registrationType): array
    {
        $summary = $this->emptySummary();

        $this->registrationsByStatus($registrationType, $this->pendingStatusFor($registrationType))
            ->chunkById(100, function (Collection $registrations) use (&$summary): void {
                foreach ($registrations as $registration) {
                    $linkedItems = $this->linkedItemsFor($registration, lock: false);

                    if ($this->singleAutomaticLaunch($linkedItems, StatusLacamento::Cancelado)) {
                        $summary['candidates']++;
                        $summary['reactivated']++;

                        continue;
                    }

                    if ($linkedItems->isNotEmpty()) {
                        $summary['skipped']++;

                        continue;
                    }

                    if ($this->allocator->expectedAmountFor($registration) === null) {
                        $summary['failed']++;

                        continue;
                    }

                    $summary['candidates']++;
                    $summary['created']++;
                }
            });

        $this->registrationsByStatus($registrationType, $this->cancelledStatusFor($registrationType))
            ->chunkById(100, function (Collection $registrations) use (&$summary): void {
                foreach ($registrations as $registration) {
                    if (! $this->singleAutomaticLaunch($this->linkedItemsFor($registration, lock: false), StatusLacamento::Pendente)) {
                        $summary['skipped']++;

                        continue;
                    }

                    $summary['candidates']++;
                    $summary['cancelled']++;
                }
            });

        return $summary;
    }

    /**
     * @param  class-string<Model>  $registrationType
     */
    private function hasPendingRegistrationWithoutLink(string $registrationType): bool
    {
        return $this->registrationsByStatus($registrationType, $this->pendingStatusFor($registrationType))
            ->whereDoesntHave('lancamentoItems')
            ->exists();
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return Builder<Model>
     */
    private function registrationsByStatus(string $registrationType, int $status): Builder
    {
        return $registrationType::query()
            ->where('status', $status)
            ->orderBy('id');
    }

    /**
     * @return array<string, class-string<Model>>
     */
    private function typesFor(?string $type): array
    {
        if ($type === null || $type === '') {
            return self::registrationTypes();
        }

        if (! array_key_exists($type, self::registrationTypes())) {
            throw new RuntimeException("Tipo de inscrição inválido: {$type}.");
        }

        return [$type => self::registrationTypes()[$type]];
    }

    /**
     * @param  class-string<Model>  $registrationType
     */
    private function ensureSupportedRegistrationType(string $registrationType): void
    {
        if (! in_array($registrationType, self::registrationTypes(), true)) {
            throw new RuntimeException("Tipo de inscrição não suportado: {$registrationType}.");
        }
    }

    /**
     * @param  class-string<Model>  $registrationType
     */
    private function findRegistration(string $registrationType, int $registrationId, bool $lock = false): ?Model
    {
        $query = $registrationType::query()->whereKey($registrationId);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    /**
     * @return Collection<int, LancamentoItem>
     */
    private function linkedItemsFor(Model $registration, bool $lock = true): Collection
    {
        $query = LancamentoItem::query()
            ->where('registration_type', $registration::class)
            ->where('registration_id', $registration->getKey())
            ->with('lancamento');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, LancamentoItem>  $linkedItems
     */
    private function singleAutomaticLaunch(Collection $linkedItems, StatusLacamento $status): ?Lancamento
    {
        if ($linkedItems->count() !== 1) {
            return null;
        }

        $lancamento = $linkedItems->first()?->lancamento;

        if (! $lancamento instanceof Lancamento) {
            return null;
        }

        if (
            $lancamento->origin !== Lancamento::ORIGIN_AUTO_REGISTRATION
            || $lancamento->status !== $status
            || $lancamento->items()->count() !== 1
        ) {
            return null;
        }

        return $lancamento;
    }

    private function expectedAmountFor(Model $registration): int
    {
        $amount = $this->allocator->expectedAmountFor($registration);

        if ($amount === null || $amount <= 0) {
            throw new RuntimeException($this->allocator->missingConfiguredAmountMessageFor($registration));
        }

        return $amount;
    }

    /**
     * @param  class-string<Model>  $registrationType
     */
    private function categoryForRegistrationType(string $registrationType): CategoriaLancamento
    {
        $category = $this->allocator->categoryForRegistrationType($registrationType);

        if (! $category) {
            throw new RuntimeException("Categoria padrão não encontrada para {$registrationType}.");
        }

        return $category;
    }

    private function registrationIsPending(Model $registration): bool
    {
        return match (true) {
            $registration instanceof Campista => $registration->status === StatusInscricao::Pendente,
            $registration instanceof EquipeTrabalho => $registration->status === StatusInscricaoEquipeTrabalho::Pendente,
            default => false,
        };
    }

    private function registrationIsCancelled(Model $registration): bool
    {
        return match (true) {
            $registration instanceof Campista => $registration->status === StatusInscricao::Cancelado,
            $registration instanceof EquipeTrabalho => $registration->status === StatusInscricaoEquipeTrabalho::Cancelado,
            default => false,
        };
    }

    /**
     * @param  class-string<Model>  $registrationType
     */
    private function pendingStatusFor(string $registrationType): int
    {
        return match ($registrationType) {
            Campista::class => StatusInscricao::Pendente->value,
            EquipeTrabalho::class => StatusInscricaoEquipeTrabalho::Pendente->value,
            default => throw new RuntimeException("Tipo de inscrição não suportado: {$registrationType}."),
        };
    }

    /**
     * @param  class-string<Model>  $registrationType
     */
    private function cancelledStatusFor(string $registrationType): int
    {
        return match ($registrationType) {
            Campista::class => StatusInscricao::Cancelado->value,
            EquipeTrabalho::class => StatusInscricaoEquipeTrabalho::Cancelado->value,
            default => throw new RuntimeException("Tipo de inscrição não suportado: {$registrationType}."),
        };
    }

    private function launchName(Model $registration, string $registrationName): string
    {
        $prefix = $registration instanceof EquipeTrabalho
            ? 'Contribuição equipe'
            : 'Inscrição';

        return $prefix.' - '.$registrationName;
    }

    private function registrationName(Model $registration): string
    {
        return (string) ($registration->getAttribute('nome') ?? 'Inscrição #'.$registration->getKey());
    }

    private function descriptionForContext(string $originContext): string
    {
        return $originContext === Lancamento::ORIGIN_CONTEXT_DAILY_RECONCILIATION
            ? 'Lançamento automático de regularização diária.'
            : 'Lançamento automático gerado a partir da inscrição.';
    }

    /**
     * @param  class-string<Model>  $registrationType
     * @return array{action: string, reason: ?string, registration_type: class-string<Model>, registration_id: int, lancamento_id: int|null}
     */
    private function result(string $action, ?string $reason, string $registrationType, int $registrationId, ?int $lancamentoId = null): array
    {
        return [
            'action' => $action,
            'reason' => $reason,
            'registration_type' => $registrationType,
            'registration_id' => $registrationId,
            'lancamento_id' => $lancamentoId,
        ];
    }

    /**
     * @return array{candidates: int, created: int, reactivated: int, cancelled: int, skipped: int, failed: int}
     */
    private function emptySummary(): array
    {
        return [
            'candidates' => 0,
            'created' => 0,
            'reactivated' => 0,
            'cancelled' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
    }

    /**
     * @param  array{candidates: int, created: int, reactivated: int, cancelled: int, skipped: int, failed: int}  $summary
     * @param  array{action: string, reason: ?string, registration_type: class-string<Model>, registration_id: int, lancamento_id: int|null}  $result
     */
    private function mergeResult(array &$summary, array $result): void
    {
        match ($result['action']) {
            self::ACTION_CREATED => $summary['created']++,
            self::ACTION_REACTIVATED => $summary['reactivated']++,
            self::ACTION_CANCELLED => $summary['cancelled']++,
            self::ACTION_SKIPPED => $summary['skipped']++,
            default => null,
        };

        if (in_array($result['action'], [self::ACTION_CREATED, self::ACTION_REACTIVATED, self::ACTION_CANCELLED], true)) {
            $summary['candidates']++;
        }
    }
}
