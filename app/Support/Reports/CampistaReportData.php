<?php

namespace App\Support\Reports;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\LancamentoItem;
use App\Models\Tribo;
use App\Models\User;
use App\Support\Campistas\ParishCommunityLabels;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampistaReportData
{
    /**
     * @return array<int, array{value: string, label: string, title: string, description: string, sensitive: bool}>
     */
    public function availableTypes(User $user): array
    {
        return collect(CampistaReportType::cases())
            ->filter(fn (CampistaReportType $type): bool => $type->canBeAccessedBy($user))
            ->map(fn (CampistaReportType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
                'title' => $type->title(),
                'description' => $type->description(),
                'sensitive' => $type->isSensitive(),
            ])
            ->values()
            ->all();
    }

    public function payload(CampistaReportType $type, array $filters, User $user): array
    {
        $records = $this->records($filters);
        $canUseSensitiveHealth = $user->can('view_sensitive_health_campista');
        $showSensitiveHealth = $this->canExposeSensitiveHealth($filters, $user);
        $showPaymentData = $this->canExposePaymentData($filters, $user);

        if ($type === CampistaReportType::SensitiveHealth) {
            $records = $records->filter(fn (Campista $campista): bool => $this->hasSensitiveHealthFlag($campista))->values();
        }

        return [
            'type' => $type,
            'title' => $type->title(),
            'description' => $type->description(),
            'generatedAt' => now()->format('d/m/Y H:i'),
            'filters' => $this->filterSummary($filters),
            'canUseSensitiveHealth' => $canUseSensitiveHealth,
            'showSensitiveHealth' => $showSensitiveHealth,
            'recordsCount' => $records->count(),
            'fichas' => $type === CampistaReportType::RegistrationFichas
                ? $records->map(fn (Campista $campista): array => $this->ficha($campista, $showSensitiveHealth, $showPaymentData))->all()
                : [],
            'tribes' => $type === CampistaReportType::TribeQuadrant
                ? $this->tribeGroups($records)
                : [],
            'medicalRows' => $type === CampistaReportType::SensitiveHealth
                ? $records->map(fn (Campista $campista): array => $this->medicalRow($campista, $showSensitiveHealth))->all()
                : [],
            'missionRows' => $type === CampistaReportType::MissionContacts
                ? $records->map(fn (Campista $campista): array => $this->missionRow($campista))->all()
                : [],
        ];
    }

    public function statusOptions(): array
    {
        return collect(StatusInscricao::cases())
            ->mapWithKeys(fn (StatusInscricao $status): array => [$status->value => $status->getLabel()])
            ->all();
    }

    public function tribeOptions(): array
    {
        return Tribo::query()
            ->orderBy('cor')
            ->pluck('cor', 'id')
            ->all();
    }

    private function records(array $filters): Collection
    {
        return Campista::query()
            ->with(['tribo', 'lancamentoItems.lancamento'])
            ->when(
                $this->statusValues($filters) !== [],
                fn (Builder $query): Builder => $query->whereIn('status', $this->statusValues($filters)),
                fn (Builder $query): Builder => $query->whereIn('status', [
                    StatusInscricao::Pendente->value,
                    StatusInscricao::Pago->value,
                ]),
            )
            ->when($this->tribeIds($filters) !== [], fn (Builder $query): Builder => $query->whereIn('tribo_id', $this->tribeIds($filters)))
            ->when($this->presence($filters) !== null, fn (Builder $query): Builder => $query->where('presenca', $this->presence($filters)))
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): Builder {
                $search = trim((string) $filters['search']);

                return $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('nome', 'like', "%{$search}%")
                        ->orWhere('form_data->telefone_reponsavel_nome_1', 'like', "%{$search}%")
                        ->orWhere('form_data->bairro', 'like', "%{$search}%")
                        ->orWhere('form_data->cidade', 'like', "%{$search}%");
                });
            })
            ->orderBy('nome')
            ->get();
    }

    private function ficha(Campista $campista, bool $showSensitiveHealth, bool $showPaymentData): array
    {
        $formData = $campista->form_data ?? [];
        $status = $this->statusEnum($campista);
        $payment = $this->paymentEnum($campista);
        $takesMedicine = $this->truthy(data_get($formData, 'toma_remedio'));
        $hasRecommendation = $this->truthy(data_get($formData, 'tem_recomendacao'));

        return [
            'id' => $campista->getKey(),
            'name' => $campista->nome,
            'avatar_url' => $this->avatarUrl($campista),
            'created_at' => $campista->created_at?->format('d/m/Y H:i'),
            'status' => [
                'label' => $status?->getLabel() ?? 'Sem status',
                'tone' => $this->statusTone($status),
                'icon' => $this->filledReportIcon($status?->getIcon() ?? 'heroicon-o-question-mark-circle'),
                'color' => $this->summaryColor($status?->getColor(), $this->statusTone($status)),
                'accent' => $this->summaryAccent($status?->getColor(), $this->statusTone($status)),
            ],
            'tribe' => [
                'label' => $campista->tribo?->cor ?? 'Sem tribo',
                'accent' => $this->tribeAccent($campista->tribo?->cor) ?? $this->summaryAccent('neutral'),
            ],
            'summary' => [
                [
                    'label' => 'Status',
                    'value' => $status?->getLabel() ?? 'Sem status',
                    'tone' => $this->statusTone($status),
                    'icon' => $this->filledReportIcon($status?->getIcon() ?? 'heroicon-o-question-mark-circle'),
                    'color' => $this->summaryColor($status?->getColor(), $this->statusTone($status)),
                    'accent' => $this->summaryAccent($status?->getColor(), $this->statusTone($status)),
                ],
                [
                    'label' => 'Pagamento',
                    'value' => $payment?->getLabel() ?? 'Não informado',
                    'tone' => $this->paymentTone($payment),
                    'icon' => $this->filledReportIcon($payment?->getIcon() ?? 'heroicon-o-credit-card'),
                    'color' => $this->summaryColor($payment?->getColor(), $this->paymentTone($payment)),
                    'accent' => $this->summaryAccent($payment?->getColor(), $this->paymentTone($payment)),
                ],
                [
                    'label' => 'Presença',
                    'value' => $campista->presenca ? 'Confirmada' : 'Pendente',
                    'tone' => $campista->presenca ? 'success' : 'warning',
                    'icon' => $campista->presenca ? 'heroicon-s-check-circle' : 'heroicon-s-clock',
                    'color' => $campista->presenca ? 'success' : 'warning',
                    'accent' => $this->summaryAccent($campista->presenca ? 'success' : 'warning'),
                ],
                [
                    'label' => 'Tribo',
                    'value' => $campista->tribo?->cor ?? 'Sem tribo',
                    'tone' => $campista->tribo ? 'tribe' : 'neutral',
                    'icon' => 'heroicon-s-flag',
                    'color' => $campista->tribo ? 'tribe' : 'neutral',
                    'accent' => $this->tribeAccent($campista->tribo?->cor) ?? $this->summaryAccent('neutral'),
                ],
            ],
            'sections' => [
                [
                    'title' => 'Dados pessoais',
                    'area' => 'personal',
                    'fields' => [
                        ['label' => 'Nome completo', 'value' => $campista->nome],
                        ['label' => 'Nascimento', 'value' => data_get($formData, 'data_nacimento')],
                        ['label' => 'Sexo', 'value' => $this->sexLabel(data_get($formData, 'sexo'))],
                        ['label' => 'Telefone', 'value' => data_get($formData, 'telefone_campista')],
                        ['label' => 'Rede social', 'value' => data_get($formData, 'rede_social')],
                        ['label' => 'Camiseta', 'value' => $this->shirtLabel($formData)],
                        ['label' => 'Altura', 'value' => $this->withSuffix(data_get($formData, 'altura'), 'cm')],
                        ['label' => 'Peso', 'value' => $this->withSuffix(data_get($formData, 'peso'), 'kg')],
                    ],
                ],
                [
                    'title' => 'Contato e responsável',
                    'area' => 'contact',
                    'fields' => [
                        ['label' => 'Responsável', 'value' => $this->responsibleName($campista)],
                        ['label' => 'Telefone do responsável', 'value' => $this->responsiblePhone($campista)],
                    ],
                ],
                [
                    'title' => 'Endereço',
                    'area' => 'address',
                    'fields' => [
                        ['label' => 'CEP', 'value' => data_get($formData, 'cep')],
                        ['label' => 'Rua', 'value' => data_get($formData, 'rua')],
                        ['label' => 'Número', 'value' => data_get($formData, 'numero')],
                        ['label' => 'Bairro', 'value' => data_get($formData, 'bairro')],
                        ['label' => 'Cidade', 'value' => data_get($formData, 'cidade')],
                        ['label' => 'Estado', 'value' => data_get($formData, 'estado')],
                        ['label' => 'Complemento', 'value' => data_get($formData, 'ponto_referencia')],
                    ],
                ],
                [
                    'title' => 'Comunidade e experiência',
                    'area' => 'community',
                    'fields' => [
                        ['label' => 'Paróquia', 'value' => $this->parishLabel(data_get($formData, 'paroquia'))],
                        ['label' => 'Comunidade', 'value' => $this->communityLabel(data_get($formData, 'paroquia'), data_get($formData, 'comunidade'))],
                        ['label' => 'Já participou?', 'value' => $this->booleanLabel(data_get($formData, 'ja_participou_retiro'))],
                        ['label' => 'Retiro/acampamento', 'value' => $this->listLabel(data_get($formData, 'retiro_que_participou'))],
                        ['label' => 'Conhece participante?', 'value' => $this->booleanLabel(data_get($formData, 'algum_parente'))],
                        ['label' => 'Nomes indicados', 'value' => $this->listLabel(data_get($formData, 'algum_parente_participante'))],
                        ['label' => 'Declaração', 'value' => $this->declarationLabel(data_get($formData, 'declaro')), 'wide' => true],
                    ],
                ],
                [
                    'title' => 'Saúde e cuidados',
                    'area' => 'health',
                    'fields' => [
                        ['label' => 'Toma remédio?', 'value' => $this->sensitiveBooleanLabel($takesMedicine, $showSensitiveHealth), 'tone' => $takesMedicine ? 'warning' : 'success'],
                        ['label' => 'Detalhes do remédio', 'value' => $this->sensitiveValue(data_get($formData, 'remedio'), $takesMedicine, $showSensitiveHealth)],
                        ['label' => 'Tem recomendação?', 'value' => $this->sensitiveBooleanLabel($hasRecommendation, $showSensitiveHealth), 'tone' => $hasRecommendation ? 'warning' : 'success'],
                        ['label' => 'Recomendação de cuidado', 'value' => $this->sensitiveValue(data_get($formData, 'recomendacao'), $hasRecommendation, $showSensitiveHealth)],
                    ],
                ],
            ],
            'can_view_payments' => $showPaymentData,
            'payments' => $showPaymentData ? $this->linkedPaymentsData($campista) : [],
        ];
    }

    /**
     * @return array<int, array{name: string, amount: string, date: string, method: array{label: string, icon: string, color: string, accent: string}, status: array{label: string, icon: string, color: string, accent: string}, url: ?string}>
     */
    private function linkedPaymentsData(Campista $campista): array
    {
        $items = $campista->relationLoaded('lancamentoItems')
            ? $campista->lancamentoItems
            : $campista->lancamentoItems()->with('lancamento')->get();

        return $items
            ->sortByDesc('id')
            ->map(fn (LancamentoItem $payment): array => $this->linkedPaymentData($payment))
            ->values()
            ->all();
    }

    /**
     * @return array{name: string, amount: string, date: string, method: array{label: string, icon: string, color: string, accent: string}, status: array{label: string, icon: string, color: string, accent: string}, url: ?string}
     */
    private function linkedPaymentData(LancamentoItem $payment): array
    {
        $lancamento = $payment->lancamento;
        $method = $lancamento?->forma_pagamento;
        $status = $lancamento?->status;

        return [
            'name' => $lancamento?->nome ?? 'Lançamento removido',
            'amount' => $this->money((int) $payment->valor),
            'date' => $lancamento?->data ? Carbon::parse($lancamento->data)->format('d/m/Y') : 'Sem data',
            'method' => [
                'label' => $method?->getLabel() ?? 'Sem forma',
                'icon' => $this->filledReportIcon($method?->getIcon() ?? 'heroicon-o-credit-card'),
                'color' => $this->summaryColor($method?->getColor(), 'neutral'),
                'accent' => $this->summaryAccent($method?->getColor(), 'neutral'),
            ],
            'status' => [
                'label' => $status?->getLabel() ?? 'Sem status',
                'icon' => $this->filledReportIcon($status?->getIcon() ?? 'heroicon-o-question-mark-circle'),
                'color' => $this->summaryColor($status?->getColor(), 'neutral'),
                'accent' => $this->summaryAccent($status?->getColor(), 'neutral'),
            ],
            'url' => $lancamento ? route('filament.admin.resources.lancamentos.view', ['record' => $lancamento]) : null,
        ];
    }

    private function money(int $amount): string
    {
        return 'R$ '.number_format(abs($amount) / 100, 2, ',', '.');
    }

    private function filledReportIcon(?string $icon): string
    {
        $icon = filled($icon) ? (string) $icon : 'heroicon-o-question-mark-circle';

        return Str::startsWith($icon, 'heroicon-o-')
            ? Str::replaceStart('heroicon-o-', 'heroicon-s-', $icon)
            : $icon;
    }

    private function statusTone(?StatusInscricao $status): string
    {
        return match ($status) {
            StatusInscricao::Pago => 'success',
            StatusInscricao::Cancelado => 'danger',
            StatusInscricao::Pendente => 'warning',
            default => 'neutral',
        };
    }

    private function paymentTone(?FormaPagamento $payment): string
    {
        return match ($payment) {
            FormaPagamento::Pix => 'info',
            FormaPagamento::Dinheiro => 'success',
            FormaPagamento::Cartao => 'warning',
            FormaPagamento::NaoPago => 'danger',
            default => 'neutral',
        };
    }

    private function summaryColor(string|array|null $color, string $fallback = 'neutral'): string
    {
        if (is_array($color)) {
            $color = collect($color)
                ->filter(fn (mixed $value): bool => is_string($value) && filled($value))
                ->first();
        }

        return is_string($color) && filled($color) ? $color : $fallback;
    }

    private function summaryAccent(string|array|null $color, string $fallback = 'neutral'): string
    {
        return match ($this->summaryColor($color, $fallback)) {
            'success' => '#22c55e',
            'warning' => '#facc15',
            'danger' => '#fb7185',
            'info' => '#9ddbef',
            'teal' => '#2dd4bf',
            'orange' => '#f46b12',
            'violet' => '#a78bfa',
            default => '#94a3b8',
        };
    }

    private function tribeAccent(?string $color): ?string
    {
        if (blank($color)) {
            return null;
        }

        $normalized = Str::lower(Str::ascii(trim($color)));

        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $normalized) === 1) {
            return $normalized;
        }

        return match ($normalized) {
            'azul' => '#2563eb',
            'vermelha', 'vermelho' => '#dc2626',
            'verde' => '#16a34a',
            'amarela', 'amarelo' => '#eab308',
            'roxa', 'roxo' => '#7c3aed',
            'laranja' => '#f97316',
            'rosa' => '#ec4899',
            'branca', 'branco' => '#f8fafc',
            'preta', 'preto' => '#111827',
            'cinza' => '#64748b',
            default => null,
        };
    }

    private function tribeGroups(Collection $records): array
    {
        return $records
            ->groupBy(fn (Campista $campista): string => $campista->tribo?->cor ?? 'Sem tribo')
            ->sortKeys()
            ->map(fn (Collection $campistas, string $tribe): array => [
                'tribe' => $tribe,
                'count' => $campistas->count(),
                'records' => $campistas
                    ->sortBy('nome')
                    ->map(fn (Campista $campista): array => [
                        'name' => $campista->nome,
                        'status' => $this->statusEnum($campista)?->getLabel() ?? 'Sem status',
                        'age' => $this->age(data_get($campista->form_data ?? [], 'data_nacimento')),
                        'presence' => $campista->presenca ? 'Presente' : 'Pendente',
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    private function medicalRow(Campista $campista, bool $showSensitiveHealth): array
    {
        $formData = $campista->form_data ?? [];

        return [
            'name' => $campista->nome,
            'tribe' => $campista->tribo?->cor ?? 'Sem tribo',
            'age' => $this->age(data_get($formData, 'data_nacimento')),
            'medicine' => $this->sensitiveValue(data_get($formData, 'remedio'), $this->truthy(data_get($formData, 'toma_remedio')), $showSensitiveHealth),
            'recommendation' => $this->sensitiveValue(data_get($formData, 'recomendacao'), $this->truthy(data_get($formData, 'tem_recomendacao')), $showSensitiveHealth),
            'responsible' => $this->responsibleName($campista),
            'phone' => $this->responsiblePhone($campista),
        ];
    }

    private function missionRow(Campista $campista): array
    {
        return [
            'name' => $campista->nome,
            'responsible' => $this->responsibleName($campista),
            'phone' => $this->responsiblePhone($campista),
            'address' => $this->address($campista),
            'neighborhood' => data_get($campista->form_data ?? [], 'bairro'),
            'city' => data_get($campista->form_data ?? [], 'cidade'),
            'reference' => data_get($campista->form_data ?? [], 'ponto_referencia'),
        ];
    }

    private function filterSummary(array $filters): array
    {
        return [
            'Status' => $this->statusLabels($filters),
            'Tribo' => $this->tribeLabels($filters),
            'Presença' => match ($this->presence($filters)) {
                true => 'Confirmada',
                false => 'Pendente',
                null => 'Todas',
            },
            'Busca' => filled($filters['search'] ?? null) ? trim((string) $filters['search']) : 'Sem busca',
        ];
    }

    private function statusValues(array $filters): array
    {
        return $this->integerList($filters['status'] ?? []);
    }

    private function tribeIds(array $filters): array
    {
        return $this->integerList($filters['tribo_id'] ?? []);
    }

    private function presence(array $filters): ?bool
    {
        if (! array_key_exists('presenca', $filters) || $filters['presenca'] === null || $filters['presenca'] === '') {
            return null;
        }

        return (bool) (int) $filters['presenca'];
    }

    private function canExposeSensitiveHealth(array $filters, User $user): bool
    {
        return $user->can('view_sensitive_health_campista')
            && $this->truthy($filters['show_sensitive_health'] ?? false)
            && $this->truthy($filters['confirm_sensitive_health'] ?? false);
    }

    private function canExposePaymentData(array $filters, User $user): bool
    {
        return $user->can('view_any_lancamento')
            && $user->can('view_lancamento')
            && $this->truthy($filters['show_payment_data'] ?? false);
    }

    private function integerList(mixed $values): array
    {
        $values = is_array($values) ? $values : [$values];

        return collect($values)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => (int) $value)
            ->values()
            ->all();
    }

    private function statusLabels(array $filters): string
    {
        $values = $this->statusValues($filters);

        if ($values === []) {
            return 'Pendente e Pago';
        }

        return collect($values)
            ->map(fn (int $value): ?string => StatusInscricao::tryFrom($value)?->getLabel())
            ->filter()
            ->join(', ');
    }

    private function tribeLabels(array $filters): string
    {
        $ids = $this->tribeIds($filters);

        if ($ids === []) {
            return 'Todas';
        }

        return Tribo::query()
            ->whereIn('id', $ids)
            ->orderBy('cor')
            ->pluck('cor')
            ->join(', ');
    }

    private function hasSensitiveHealthFlag(Campista $campista): bool
    {
        $formData = $campista->form_data ?? [];

        return $this->truthy(data_get($formData, 'toma_remedio'))
            || $this->truthy(data_get($formData, 'tem_recomendacao'));
    }

    private function avatarUrl(Campista $campista): ?string
    {
        $avatar = $campista->avatar_url;

        if (blank($avatar)) {
            return null;
        }

        return Str::startsWith($avatar, ['http://', 'https://', '/'])
            ? $avatar
            : Storage::disk('public')->url($avatar);
    }

    private function responsibleName(Campista $campista): ?string
    {
        $formData = $campista->form_data ?? [];

        return data_get($formData, 'telefone_reponsavel_nome_1')
            ?? data_get($formData, 'nome_mae')
            ?? data_get($formData, 'nome_pai');
    }

    private function responsiblePhone(Campista $campista): ?string
    {
        $formData = $campista->form_data ?? [];

        return data_get($formData, 'telefone_reponsavel_1')
            ?? data_get($formData, 'telefone_reponsavel')
            ?? data_get($formData, 'telefone_campista');
    }

    private function address(Campista $campista): string
    {
        $formData = $campista->form_data ?? [];

        return collect([
            data_get($formData, 'rua'),
            data_get($formData, 'numero'),
            data_get($formData, 'bairro'),
            data_get($formData, 'cidade'),
            data_get($formData, 'estado'),
        ])
            ->filter()
            ->join(', ');
    }

    private function age(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        try {
            $birthDate = Carbon::createFromFormat('!d/m/Y', (string) $date);
        } catch (\Throwable) {
            return null;
        }

        return $birthDate->format('d/m/Y') === $date
            ? (string) $birthDate->age
            : null;
    }

    private function statusEnum(Campista $campista): ?StatusInscricao
    {
        return $campista->status instanceof StatusInscricao
            ? $campista->status
            : StatusInscricao::tryFrom((int) $campista->status);
    }

    private function paymentEnum(Campista $campista): ?FormaPagamento
    {
        if ($campista->forma_pagamento instanceof FormaPagamento) {
            return $campista->forma_pagamento;
        }

        return blank($campista->forma_pagamento) ? null : FormaPagamento::tryFrom((int) $campista->forma_pagamento);
    }

    private function sexLabel(mixed $sex): ?string
    {
        return match ($sex) {
            'M' => 'Masculino',
            'F' => 'Feminino',
            default => filled($sex) ? (string) $sex : null,
        };
    }

    private function shirtLabel(array $formData): ?string
    {
        $size = data_get($formData, 'tamanho_camiseta');

        if ($size === 'O') {
            return filled(data_get($formData, 'tamanho_camiseta_outro'))
                ? 'Outro: '.data_get($formData, 'tamanho_camiseta_outro')
                : 'Outro';
        }

        return $size;
    }

    private function withSuffix(mixed $value, string $suffix): ?string
    {
        return filled($value) ? trim((string) $value).' '.$suffix : null;
    }

    private function parishLabel(mixed $parish): ?string
    {
        return ParishCommunityLabels::parishLabel($parish);
    }

    private function communityLabel(mixed $parish, mixed $community): ?string
    {
        return ParishCommunityLabels::communityLabel($parish, $community);
    }

    private function booleanLabel(mixed $value): string
    {
        return $this->truthy($value) ? 'Sim' : 'Não';
    }

    private function declarationLabel(mixed $value): string
    {
        return $this->truthy($value)
            ? 'Declara nunca ter participado'
            : 'Já participou de alguma edição';
    }

    private function listLabel(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return is_array($value) ? implode(', ', array_filter($value)) : (string) $value;
    }

    private function sensitiveValue(mixed $value, bool $hasSensitiveInfo, bool $canViewSensitiveHealth): ?string
    {
        if (! $hasSensitiveInfo) {
            return null;
        }

        if (! $canViewSensitiveHealth) {
            return 'Informação restrita';
        }

        return filled($value) ? (string) $value : 'Não detalhado';
    }

    private function sensitiveBooleanLabel(bool $hasSensitiveInfo, bool $showSensitiveHealth): string
    {
        if (! $hasSensitiveInfo) {
            return 'Não';
        }

        if (! $showSensitiveHealth) {
            return 'Informação restrita';
        }

        return 'Sim';
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'sim', 'yes', 'on'], true);
    }
}
