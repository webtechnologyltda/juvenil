<?php

namespace App\Support\Reports;

use App\Enums\StatusLacamento;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\LancamentoItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RegistrationPaymentReportData
{
    /**
     * @return array<int, array{
     *     registration_type: string,
     *     registration_name: string,
     *     launch_name: string,
     *     category: string,
     *     date: string,
     *     amount: string,
     *     payment_method: string,
     *     status: array{label: string, icon: string, color: string}
     * }>
     */
    public function rows(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return LancamentoItem::query()
            ->select([
                'id',
                'lancamento_id',
                'nome',
                'valor',
                'categoria_lancamento_id',
                'registration_type',
                'registration_id',
            ])
            ->with([
                'categoria:id,nome',
                'lancamento:id,nome,data,status,forma_pagamento',
                'registration' => fn (MorphTo $morphTo): MorphTo => $morphTo->constrain([
                    Campista::class => fn (Builder $query): Builder => $query->select(['id', 'nome']),
                    EquipeTrabalho::class => fn (Builder $query): Builder => $query->select(['id', 'nome']),
                ]),
            ])
            ->whereIn('registration_type', [Campista::class, EquipeTrabalho::class])
            ->whereHas('lancamento', fn (Builder $query): Builder => $query->whereIn('status', $this->statusValues($filters)))
            ->whereHasMorph('registration', [Campista::class, EquipeTrabalho::class])
            ->when(filled($search), function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHasMorph(
                            'registration',
                            [Campista::class, EquipeTrabalho::class],
                            fn (Builder $query): Builder => $query->where('nome', 'like', "%{$search}%"),
                        )
                        ->orWhere('nome', 'like', "%{$search}%")
                        ->orWhereHas('lancamento', fn (Builder $query): Builder => $query->where('nome', 'like', "%{$search}%"));
                });
            })
            ->get()
            ->map(fn (LancamentoItem $item): array => $this->row($item))
            ->sortBy([
                ['registration_type', 'asc'],
                ['registration_name', 'asc'],
                ['date', 'asc'],
            ])
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    public function statusOptions(): array
    {
        return collect($this->allowedStatuses())
            ->mapWithKeys(fn (StatusLacamento $status): array => [$status->value => $status->getLabel()])
            ->all();
    }

    /** @return array<string, string> */
    public function filterSummary(array $filters): array
    {
        return [
            'Status do pagamento' => collect($this->statusValues($filters))
                ->map(fn (int $status): ?string => StatusLacamento::tryFrom($status)?->getLabel())
                ->filter()
                ->join(', '),
            'Busca' => filled($filters['search'] ?? null)
                ? trim((string) $filters['search'])
                : 'Sem busca',
        ];
    }

    /**
     * @return array{
     *     registration_type: string,
     *     registration_name: string,
     *     launch_name: string,
     *     category: string,
     *     date: string,
     *     amount: string,
     *     payment_method: string,
     *     status: array{label: string, icon: string, color: string}
     * }
     */
    private function row(LancamentoItem $item): array
    {
        $registration = $item->registration;
        $launch = $item->lancamento;
        $status = $launch?->status;

        return [
            'registration_type' => $registration instanceof Campista ? 'Campista' : 'Equipe de trabalho',
            'registration_name' => (string) ($registration?->getAttribute('nome') ?? 'Inscrição removida'),
            'launch_name' => $launch?->nome ?? 'Lançamento removido',
            'category' => $item->categoria?->nome ?? 'Sem categoria',
            'date' => filled($launch?->data) ? Carbon::parse($launch->data)->format('d/m/Y') : 'Sem data',
            'amount' => 'R$ '.number_format(abs($item->valor) / 100, 2, ',', '.'),
            'payment_method' => $launch?->forma_pagamento?->getLabel() ?? 'Não informado',
            'status' => [
                'label' => $status?->getLabel() ?? 'Sem status',
                'icon' => $status?->getIcon() ?? 'heroicon-o-question-mark-circle',
                'color' => is_string($status?->getColor()) ? $status->getColor() : 'gray',
            ],
        ];
    }

    /** @return array<int, int> */
    private function statusValues(array $filters): array
    {
        $allowedValues = collect($this->allowedStatuses())
            ->map(fn (StatusLacamento $status): int => $status->value);

        $values = collect($filters['payment_status'] ?? [])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => (int) $value)
            ->intersect($allowedValues)
            ->unique()
            ->values()
            ->all();

        return $values !== [] ? $values : $allowedValues->all();
    }

    /** @return array<int, StatusLacamento> */
    private function allowedStatuses(): array
    {
        return [
            StatusLacamento::Pendente,
            StatusLacamento::Pago,
        ];
    }
}
