<?php

namespace App\Support\Reports;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\Tribo;
use App\Models\User;
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
        $canViewSensitiveHealth = $user->can('view_sensitive_health_campista');

        if ($type === CampistaReportType::SensitiveHealth) {
            $records = $records->filter(fn (Campista $campista): bool => $this->hasSensitiveHealthFlag($campista))->values();
        }

        return [
            'type' => $type,
            'title' => $type->title(),
            'description' => $type->description(),
            'generatedAt' => now()->format('d/m/Y H:i'),
            'filters' => $this->filterSummary($filters),
            'recordsCount' => $records->count(),
            'fichas' => $type === CampistaReportType::RegistrationFichas
                ? $records->map(fn (Campista $campista): array => $this->ficha($campista, $canViewSensitiveHealth))->all()
                : [],
            'tribes' => $type === CampistaReportType::TribeQuadrant
                ? $this->tribeGroups($records)
                : [],
            'medicalRows' => $type === CampistaReportType::SensitiveHealth
                ? $records->map(fn (Campista $campista): array => $this->medicalRow($campista))->all()
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
            ->with('tribo')
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

    private function ficha(Campista $campista, bool $canViewSensitiveHealth): array
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
            'status' => $status?->getLabel() ?? 'Sem status',
            'tribe' => $campista->tribo?->cor ?? 'Sem tribo',
            'sections' => [
                [
                    'title' => 'Dados pessoais',
                    'fields' => [
                        ['label' => 'Nome completo', 'value' => $campista->nome],
                        ['label' => 'Nascimento', 'value' => data_get($formData, 'data_nacimento')],
                        ['label' => 'Idade', 'value' => $this->age(data_get($formData, 'data_nacimento'))],
                        ['label' => 'Sexo', 'value' => $this->sexLabel(data_get($formData, 'sexo'))],
                        ['label' => 'Telefone', 'value' => data_get($formData, 'telefone_campista')],
                        ['label' => 'Camiseta', 'value' => $this->shirtLabel($formData)],
                    ],
                ],
                [
                    'title' => 'Contato e responsável',
                    'fields' => [
                        ['label' => 'Responsável', 'value' => $this->responsibleName($campista)],
                        ['label' => 'Telefone do responsável', 'value' => $this->responsiblePhone($campista)],
                    ],
                ],
                [
                    'title' => 'Endereço',
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
                    'title' => 'Saúde e cuidados',
                    'fields' => [
                        ['label' => 'Toma remédio?', 'value' => $this->booleanLabel($takesMedicine)],
                        ['label' => 'Detalhes do remédio', 'value' => $this->sensitiveValue(data_get($formData, 'remedio'), $takesMedicine, $canViewSensitiveHealth)],
                        ['label' => 'Tem recomendação?', 'value' => $this->booleanLabel($hasRecommendation)],
                        ['label' => 'Recomendação de cuidado', 'value' => $this->sensitiveValue(data_get($formData, 'recomendacao'), $hasRecommendation, $canViewSensitiveHealth)],
                    ],
                ],
                [
                    'title' => 'Controle da inscrição',
                    'fields' => [
                        ['label' => 'Status', 'value' => $status?->getLabel()],
                        ['label' => 'Pagamento', 'value' => $payment?->getLabel()],
                        ['label' => 'Presença', 'value' => $campista->presenca ? 'Confirmada' : 'Pendente'],
                        ['label' => 'Tribo', 'value' => $campista->tribo?->cor],
                        ['label' => 'Observações', 'value' => $campista->observacoes],
                    ],
                ],
            ],
        ];
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

    private function medicalRow(Campista $campista): array
    {
        $formData = $campista->form_data ?? [];

        return [
            'name' => $campista->nome,
            'tribe' => $campista->tribo?->cor ?? 'Sem tribo',
            'age' => $this->age(data_get($formData, 'data_nacimento')),
            'medicine' => filled(data_get($formData, 'remedio')) ? data_get($formData, 'remedio') : 'Não detalhado',
            'recommendation' => filled(data_get($formData, 'recomendacao')) ? data_get($formData, 'recomendacao') : 'Não detalhado',
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

    private function booleanLabel(mixed $value): string
    {
        return $this->truthy($value) ? 'Sim' : 'Não';
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'sim', 'yes', 'on'], true);
    }
}
