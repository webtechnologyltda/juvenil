<?php

namespace App\Actions\Campistas;

use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Models\Campista;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CancelCampistaRegistrationAction
{
    public const string PAYMENT_REFUND = 'refund';

    public const string PAYMENT_KEEP_PAID = 'keep_paid';

    public function __construct(
        private readonly RegistrationPaymentAllocator $paymentAllocator,
    ) {}

    public function hasPaidPayment(Campista $campista): bool
    {
        return $this->paidPaymentItemsQuery($campista)->exists();
    }

    public function execute(Campista $campista, ?string $reason, ?string $paymentAction): void
    {
        DB::transaction(function () use ($campista, $reason, $paymentAction): void {
            $campista = Campista::query()
                ->whereKey($campista->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $paidItems = $this->paidPaymentItemsQuery($campista)
                ->lockForUpdate()
                ->get();

            if ($paidItems->isNotEmpty() && ! in_array($paymentAction, [
                self::PAYMENT_REFUND,
                self::PAYMENT_KEEP_PAID,
            ], true)) {
                throw ValidationException::withMessages([
                    'payment_action' => 'Escolha se o pagamento será estornado ou mantido como pago.',
                ]);
            }

            $campista->forceFill([
                'status' => StatusInscricao::Cancelado,
                'observacoes' => filled($reason) ? trim((string) $reason) : null,
            ])->save();

            if ($paymentAction === self::PAYMENT_REFUND) {
                $this->refundPaidPayments($campista, $paidItems, $reason);
            }
        });
    }

    /**
     * @return Builder<LancamentoItem>
     */
    private function paidPaymentItemsQuery(Campista $campista): Builder
    {
        $categoryId = $this->paymentAllocator
            ->categoryForRegistrationType($campista::class)?->getKey();

        return LancamentoItem::query()
            ->where('registration_type', $campista::class)
            ->where('registration_id', $campista->getKey())
            ->when(
                $categoryId !== null,
                fn (Builder $query): Builder => $query->where('categoria_lancamento_id', $categoryId),
                fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
            )
            ->whereHas(
                'lancamento',
                fn (Builder $query): Builder => $query->where('status', StatusLacamento::Pago),
            );
    }

    /**
     * @param  Collection<int, LancamentoItem>  $paidItems
     */
    private function refundPaidPayments(Campista $campista, Collection $paidItems, ?string $reason): void
    {
        $lancamentos = Lancamento::query()
            ->whereKey($paidItems->pluck('lancamento_id')->unique()->all())
            ->where('status', StatusLacamento::Pago)
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Lancamento $lancamento): int => (int) $lancamento->getKey());

        foreach ($paidItems->groupBy('lancamento_id') as $lancamentoId => $groupedPaidItems) {
            $lancamento = $lancamentos->get((int) $lancamentoId);

            if (! $lancamento instanceof Lancamento) {
                continue;
            }

            $allItems = $lancamento->items()->lockForUpdate()->get();
            $paidItemIds = $groupedPaidItems->modelKeys();
            $refundedItems = $allItems
                ->whereIn('id', $paidItemIds)
                ->values();

            if ($refundedItems->isEmpty()) {
                continue;
            }

            $remainingItems = $allItems
                ->whereNotIn('id', $paidItemIds)
                ->values();
            $refundAmount = (int) $refundedItems->sum('valor');
            $refundNote = $this->refundNote($campista, $refundAmount, $reason);

            if ($remainingItems->isEmpty()) {
                $lancamento->forceFill([
                    'status' => StatusLacamento::Cancelado,
                    'descricao' => $this->appendObservation($lancamento->descricao, $refundNote),
                ])->save();

                continue;
            }

            $cancelledLancamento = Lancamento::query()->create([
                'nome' => Str::limit('Estorno - '.$lancamento->nome, 255, ''),
                'descricao' => $this->appendObservation($lancamento->descricao, $refundNote),
                'comprador' => $lancamento->comprador,
                'data' => $lancamento->data,
                'valor' => $this->signedTotal($lancamento, $refundedItems),
                'tipo' => $lancamento->tipo,
                'status' => StatusLacamento::Cancelado,
                'forma_pagamento' => $lancamento->forma_pagamento,
                'comprovante' => $lancamento->comprovante ?? [],
                'batch_code' => $lancamento->batch_code,
                'user_id' => auth()->id() ?? $lancamento->user_id,
                'origin' => $lancamento->origin,
                'origin_context' => $lancamento->origin_context,
            ]);

            LancamentoItem::query()
                ->whereKey($refundedItems->modelKeys())
                ->update(['lancamento_id' => $cancelledLancamento->getKey()]);

            $lancamento->forceFill([
                'valor' => $this->signedTotal($lancamento, $remainingItems),
                'descricao' => $this->appendObservation(
                    $lancamento->descricao,
                    $refundNote.' Lançamento cancelado gerado: #'.$cancelledLancamento->getKey().'.',
                ),
            ])->save();
        }
    }

    /**
     * @param  Collection<int, LancamentoItem>  $items
     */
    private function signedTotal(Lancamento $lancamento, Collection $items): int
    {
        return $this->paymentAllocator->signedTotalForItems(
            $lancamento->tipo,
            $items->map(fn (LancamentoItem $item): array => ['valor' => $item->valor])->all(),
        );
    }

    private function refundNote(Campista $campista, int $amount, ?string $reason): string
    {
        $note = sprintf(
            'Estorno registrado em %s para a inscrição #%s - %s. Quantia estornada: %s.',
            now()->format('d/m/Y H:i'),
            $campista->getKey(),
            $campista->nome,
            $this->money($amount),
        );

        if (filled($reason)) {
            $note .= ' Motivo do cancelamento: '.trim((string) $reason).'.';
        }

        if (filled(auth()->user()?->name)) {
            $note .= ' Registrado por: '.auth()->user()->name.'.';
        }

        return $note;
    }

    private function appendObservation(?string $description, string $observation): string
    {
        return filled($description)
            ? rtrim((string) $description)."\n\n".$observation
            : $observation;
    }

    private function money(int $amount): string
    {
        return 'R$ '.number_format(abs($amount) / 100, 2, ',', '.');
    }
}
