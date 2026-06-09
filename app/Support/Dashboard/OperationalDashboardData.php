<?php

namespace App\Support\Dashboard;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Support\Campistas\ParishCommunityLabels;
use App\Support\Tribes\TribeColor;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperationalDashboardData
{
    public function forFilters(array $filters): OperationalDashboardDataSet
    {
        return new OperationalDashboardDataSet(
            records: $this->records($filters),
            allRecords: $this->allRecords($filters),
        );
    }

    public function queryForFilters(array $filters, bool $validOnly = true): Builder
    {
        return $this->baseQuery($filters)
            ->when(
                $validOnly,
                fn (Builder $query): Builder => $query->whereIn('status', OperationalDashboardFilters::validStatuses($filters)),
            )
            ->with('tribo');
    }

    protected function records(array $filters): Collection
    {
        return $this->queryForFilters($filters)->get();
    }

    protected function allRecords(array $filters): Collection
    {
        return $this->queryForFilters($filters, validOnly: false)->get();
    }

    protected function baseQuery(array $filters): Builder
    {
        $tribeIds = OperationalDashboardFilters::tribeIds($filters);
        $paroquia = OperationalDashboardFilters::formFilter($filters, 'paroquia');
        $isOtherParish = $paroquia === 2;
        $communityValues = $isOtherParish ? [] : OperationalDashboardFilters::communityValues($filters);
        $communityText = $isOtherParish ? OperationalDashboardFilters::communityText($filters) : null;
        $presenca = OperationalDashboardFilters::presence($filters);

        return Campista::query()
            ->when($tribeIds !== [], fn (Builder $query): Builder => $query->whereIn('tribo_id', $tribeIds))
            ->when($paroquia !== null, fn (Builder $query): Builder => $query->where('form_data->paroquia', $paroquia))
            ->when($communityValues !== [], fn (Builder $query): Builder => $this->whereFormDataValueIn($query, 'form_data->comunidade', $communityValues))
            ->when($communityText !== null, fn (Builder $query): Builder => $query->where('form_data->comunidade', 'like', '%'.$communityText.'%'))
            ->when($presenca !== null, fn (Builder $query): Builder => $query->where('presenca', $presenca));
    }

    private function whereFormDataValueIn(Builder $query, string $path, array $values): Builder
    {
        return $query->where(function (Builder $query) use ($path, $values): void {
            foreach ($values as $value) {
                $query->orWhere($path, $value);
            }
        });
    }
}

class OperationalDashboardDataSet
{
    public function __construct(
        private readonly Collection $records,
        private readonly Collection $allRecords,
    ) {}

    public function pipeline(): array
    {
        return [
            'valid' => $this->records->count(),
            'pending_payment' => $this->records->filter(fn (Campista $campista): bool => $this->statusIs($campista, StatusInscricao::Pendente))->count(),
            'paid' => $this->records->filter(fn (Campista $campista): bool => $this->statusIs($campista, StatusInscricao::Pago))->count(),
            'awaiting_check_in' => $this->records
                ->filter(fn (Campista $campista): bool => $this->statusIs($campista, StatusInscricao::Pago) && $campista->presenca === false)
                ->count(),
            'present' => $this->records
                ->filter(fn (Campista $campista): bool => $this->statusIs($campista, StatusInscricao::Pago) && $campista->presenca === true)
                ->count(),
            'cancelled' => $this->allRecords->filter(fn (Campista $campista): bool => $this->statusIs($campista, StatusInscricao::Cancelado))->count(),
        ];
    }

    public function tribes(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $campista->tribo?->cor ?: 'Sem tribo')
            ->map->count()
            ->sortDesc()
            ->all();
    }

    public function tribeColors(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $campista->tribo?->cor ?: 'Sem tribo')
            ->map(fn (Collection $campistas): string => TribeColor::forTribe($campistas->first()?->tribo))
            ->all();
    }

    public function shirts(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $this->shirtLabel($campista))
            ->map->count()
            ->sortDesc()
            ->all();
    }

    public function communities(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $this->communityLabel($campista))
            ->map->count()
            ->sortDesc()
            ->all();
    }

    public function ages(): array
    {
        $counts = $this->records
            ->groupBy(fn (Campista $campista): string => $this->ageBucket($campista))
            ->map->count()
            ->all();

        return collect([
            'Ate 29',
            '30-34',
            '35-39',
            '40-44',
            '45-49',
            '50-54',
            '55-59',
            '60+',
            'Sem data',
        ])
            ->mapWithKeys(fn (string $bucket): array => [$bucket => $counts[$bucket] ?? 0])
            ->filter(fn (int $count): bool => $count > 0)
            ->all();
    }

    public function sexes(): array
    {
        $counts = $this->records
            ->groupBy(fn (Campista $campista): string => match ($this->formValue($campista, 'sexo')) {
                'M' => 'Masculino',
                'F' => 'Feminino',
                default => 'Sem sexo',
            })
            ->map->count()
            ->all();

        return collect([
            'Masculino',
            'Feminino',
            'Sem sexo',
        ])
            ->mapWithKeys(fn (string $sex): array => [$sex => $counts[$sex] ?? 0])
            ->filter(fn (int $count): bool => $count > 0)
            ->all();
    }

    public function healthSummary(): array
    {
        $medicine = $this->records->filter(fn (Campista $campista): bool => $this->truthy($this->formValue($campista, 'toma_remedio')));
        $recommendation = $this->records->filter(fn (Campista $campista): bool => $this->truthy($this->formValue($campista, 'tem_recomendacao')));

        return [
            'medicine' => $medicine->count(),
            'recommendation' => $recommendation->count(),
            'both' => $medicine->intersect($recommendation)->count(),
        ];
    }

    public function sensitiveHealthRecords(): Collection
    {
        return $this->records
            ->filter(fn (Campista $campista): bool => $this->truthy($this->formValue($campista, 'toma_remedio'))
                || $this->truthy($this->formValue($campista, 'tem_recomendacao')))
            ->values();
    }

    public function pendingTasks(): Collection
    {
        return $this->records
            ->map(function (Campista $campista): array {
                $issues = collect([
                    $this->isBlank($this->formValue($campista, 'telefone_campista')) ? 'Sem telefone do campista' : null,
                    $this->isBlank($this->formValue($campista, 'telefone_reponsavel_1')) ? 'Sem telefone do responsavel' : null,
                    $this->isBlank($this->formValue($campista, 'telefone_reponsavel_nome_1')) ? 'Sem nome do responsavel' : null,
                    $this->isBlank($this->formValue($campista, 'paroquia')) || $this->isBlank($this->formValue($campista, 'comunidade')) ? 'Sem paroquia/comunidade' : null,
                    $this->isBlank($this->formValue($campista, 'tamanho_camiseta')) ? 'Sem tamanho de camiseta' : null,
                    $this->isBlank($campista->avatar_url) ? 'Sem foto' : null,
                ])->filter()->values()->all();

                return [
                    'campista' => $campista,
                    'issues' => $issues,
                ];
            })
            ->filter(fn (array $row): bool => $row['issues'] !== [])
            ->values();
    }

    public function registrationsByDay(): array
    {
        return $this->records
            ->groupBy(fn (Campista $campista): string => $campista->created_at?->format('d/m') ?? 'Sem data')
            ->map->count()
            ->all();
    }

    private function statusIs(Campista $campista, StatusInscricao $status): bool
    {
        return $campista->status instanceof StatusInscricao
            ? $campista->status === $status
            : (int) $campista->status === $status->value;
    }

    private function formValue(Campista $campista, string $key): mixed
    {
        return data_get($campista->form_data ?? [], $key);
    }

    private function shirtLabel(Campista $campista): string
    {
        $size = $this->formValue($campista, 'tamanho_camiseta');

        if ($this->isBlank($size)) {
            return 'Sem tamanho';
        }

        if ($size !== 'O') {
            return (string) $size;
        }

        $otherSize = $this->formValue($campista, 'tamanho_camiseta_outro');

        return $this->isBlank($otherSize)
            ? 'Outros'
            : 'Outros: '.$otherSize;
    }

    private function communityLabel(Campista $campista): string
    {
        $paroquia = $this->formValue($campista, 'paroquia');
        $comunidade = $this->formValue($campista, 'comunidade');

        if ($this->isBlank($paroquia) || $this->isBlank($comunidade)) {
            return 'Sem comunidade';
        }

        return ParishCommunityLabels::combinedLabel($paroquia, $comunidade, short: true);
    }

    private function ageBucket(Campista $campista): string
    {
        $date = $this->formValue($campista, 'data_nacimento');

        if ($this->isBlank($date)) {
            return 'Sem data';
        }

        try {
            $birthDate = Carbon::createFromFormat('!d/m/Y', (string) $date);
        } catch (\Throwable) {
            return 'Sem data';
        }

        if ($birthDate === null || $birthDate->format('d/m/Y') !== (string) $date) {
            return 'Sem data';
        }

        $age = $birthDate->age;

        return match (true) {
            $age < 30 => 'Ate 29',
            $age <= 34 => '30-34',
            $age <= 39 => '35-39',
            $age <= 44 => '40-44',
            $age <= 49 => '45-49',
            $age <= 54 => '50-54',
            $age <= 59 => '55-59',
            default => '60+',
        };
    }

    private function truthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'sim', 'yes', 'on'], true);
        }

        return false;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }
}
