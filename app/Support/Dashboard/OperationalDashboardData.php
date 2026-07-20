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
            baseQuery: $this->baseQuery($filters),
            validStatuses: OperationalDashboardFilters::validStatuses($filters),
        );
    }

    public function queryForFilters(array $filters, bool $validOnly = true): Builder
    {
        return $this->baseQuery($filters)
            ->when(
                $validOnly,
                fn (Builder $query): Builder => $query->whereIn('status', OperationalDashboardFilters::validStatuses($filters)),
            )
            ->with('tribo')
            ->oldest('id');
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
    private ?Collection $tribeDistributionRows = null;

    public function __construct(
        private readonly Builder $baseQuery,
        private readonly array $validStatuses,
    ) {}

    public function pipeline(): array
    {
        $rows = $this->query()
            ->select(['status', 'presenca'])
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status', 'presenca')
            ->toBase()
            ->get();

        $valid = 0;
        $pendingPayment = 0;
        $paid = 0;
        $awaitingCheckIn = 0;
        $present = 0;
        $cancelled = 0;

        foreach ($rows as $row) {
            $status = (int) $row->status;
            $presence = (bool) $row->presenca;
            $count = (int) $row->aggregate;

            if ($status === StatusInscricao::Cancelado->value) {
                $cancelled += $count;
            }

            if (! in_array($status, $this->validStatuses, true)) {
                continue;
            }

            $valid += $count;

            if ($status === StatusInscricao::Pendente->value) {
                $pendingPayment += $count;
            }

            if ($status !== StatusInscricao::Pago->value) {
                continue;
            }

            $paid += $count;

            if ($presence) {
                $present += $count;
            } else {
                $awaitingCheckIn += $count;
            }
        }

        return [
            'valid' => $valid,
            'pending_payment' => $pendingPayment,
            'paid' => $paid,
            'awaiting_check_in' => $awaitingCheckIn,
            'present' => $present,
            'cancelled' => $cancelled,
        ];
    }

    public function tribes(): array
    {
        $counts = collect();

        foreach ($this->getTribeDistributionRows() as $row) {
            $label = filled($row->cor) ? (string) $row->cor : 'Sem tribo';
            $counts->put($label, (int) $counts->get($label, 0) + (int) $row->aggregate);
        }

        return $counts->sortDesc()->all();
    }

    public function tribeColors(): array
    {
        $colors = [];

        foreach ($this->getTribeDistributionRows() as $row) {
            $label = filled($row->cor) ? (string) $row->cor : 'Sem tribo';

            $colors[$label] ??= TribeColor::resolve($row->cor_hex, $row->cor);
        }

        return $colors;
    }

    public function shirts(): array
    {
        $rows = $this->validQuery()
            ->select([
                'form_data->tamanho_camiseta as shirt_size',
                'form_data->tamanho_camiseta_outro as other_shirt_size',
            ])
            ->selectRaw('COUNT(*) as aggregate, MIN(id) as first_id')
            ->groupBy('form_data->tamanho_camiseta', 'form_data->tamanho_camiseta_outro')
            ->orderBy('first_id')
            ->toBase()
            ->get();

        $counts = collect();

        foreach ($rows as $row) {
            $label = $this->shirtLabel($row->shirt_size, $row->other_shirt_size);
            $counts->put($label, (int) $counts->get($label, 0) + (int) $row->aggregate);
        }

        return $counts->sortDesc()->all();
    }

    public function communities(): array
    {
        $rows = $this->validQuery()
            ->select([
                'form_data->paroquia as parish',
                'form_data->comunidade as community',
            ])
            ->selectRaw('COUNT(*) as aggregate, MIN(id) as first_id')
            ->groupBy('form_data->paroquia', 'form_data->comunidade')
            ->orderBy('first_id')
            ->toBase()
            ->get();

        $counts = collect();

        foreach ($rows as $row) {
            $label = $this->communityLabel($row->parish, $row->community);
            $counts->put($label, (int) $counts->get($label, 0) + (int) $row->aggregate);
        }

        return $counts->sortDesc()->all();
    }

    public function ages(): array
    {
        $rows = $this->validQuery()
            ->select('form_data->data_nacimento as birth_date')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('form_data->data_nacimento')
            ->toBase()
            ->get();

        $counts = collect();

        foreach ($rows as $row) {
            $bucket = $this->ageBucket($row->birth_date);
            $counts->put($bucket, (int) $counts->get($bucket, 0) + (int) $row->aggregate);
        }

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
            ->mapWithKeys(fn (string $bucket): array => [$bucket => (int) $counts->get($bucket, 0)])
            ->filter(fn (int $count): bool => $count > 0)
            ->all();
    }

    public function sexes(): array
    {
        $rows = $this->validQuery()
            ->select('form_data->sexo as sex')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('form_data->sexo')
            ->toBase()
            ->get();

        $counts = collect();

        foreach ($rows as $row) {
            $label = match ($row->sex) {
                'M' => 'Masculino',
                'F' => 'Feminino',
                default => 'Sem sexo',
            };

            $counts->put($label, (int) $counts->get($label, 0) + (int) $row->aggregate);
        }

        return collect([
            'Masculino',
            'Feminino',
            'Sem sexo',
        ])
            ->mapWithKeys(fn (string $sex): array => [$sex => (int) $counts->get($sex, 0)])
            ->filter(fn (int $count): bool => $count > 0)
            ->all();
    }

    public function healthSummary(): array
    {
        $rows = $this->validQuery()
            ->select([
                'form_data->toma_remedio as medicine_value',
                'form_data->tem_recomendacao as recommendation_value',
            ])
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('form_data->toma_remedio', 'form_data->tem_recomendacao')
            ->toBase()
            ->get();

        $medicine = 0;
        $recommendation = 0;
        $both = 0;

        foreach ($rows as $row) {
            $count = (int) $row->aggregate;
            $usesMedicine = $this->truthy($row->medicine_value);
            $hasRecommendation = $this->truthy($row->recommendation_value);

            if ($usesMedicine) {
                $medicine += $count;
            }

            if ($hasRecommendation) {
                $recommendation += $count;
            }

            if ($usesMedicine && $hasRecommendation) {
                $both += $count;
            }
        }

        return [
            'medicine' => $medicine,
            'recommendation' => $recommendation,
            'both' => $both,
        ];
    }

    public function sensitiveHealthRecords(): Collection
    {
        return $this->validQuery()
            ->where(function (Builder $query): void {
                $this->applyTruthyConditions($query, [
                    'form_data->toma_remedio',
                    'form_data->tem_recomendacao',
                ]);
            })
            ->with('tribo')
            ->oldest('id')
            ->get();
    }

    public function pendingTasks(): Collection
    {
        return $this->validQuery()
            ->where(function (Builder $query): void {
                $this->applyBlankConditions($query, [
                    'avatar_url',
                    'form_data->telefone_campista',
                    'form_data->telefone_reponsavel_1',
                    'form_data->telefone_reponsavel_nome_1',
                    'form_data->paroquia',
                    'form_data->comunidade',
                    'form_data->tamanho_camiseta',
                ]);
            })
            ->oldest('id')
            ->get()
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
        $rows = $this->validQuery()
            ->selectRaw('DATE(created_at) as registration_date, COUNT(*) as aggregate, MIN(id) as first_id')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('first_id')
            ->toBase()
            ->get();

        $registrations = [];

        foreach ($rows as $row) {
            $label = $row->registration_date === null
                ? 'Sem data'
                : Carbon::parse($row->registration_date)->format('d/m');

            $registrations[$label] = ($registrations[$label] ?? 0) + (int) $row->aggregate;
        }

        return $registrations;
    }

    private function query(): Builder
    {
        return clone $this->baseQuery;
    }

    private function validQuery(): Builder
    {
        return $this->query()->whereIn('status', $this->validStatuses);
    }

    private function getTribeDistributionRows(): Collection
    {
        return $this->tribeDistributionRows ??= $this->validQuery()
            ->leftJoin('tribos', 'campistas.tribo_id', '=', 'tribos.id')
            ->select([
                'campistas.tribo_id',
                'tribos.cor',
                'tribos.cor_hex',
            ])
            ->selectRaw('COUNT(*) as aggregate, MIN(campistas.id) as first_id')
            ->groupBy('campistas.tribo_id', 'tribos.cor', 'tribos.cor_hex')
            ->orderBy('first_id')
            ->toBase()
            ->get();
    }

    private function applyTruthyConditions(Builder $query, array $columns): void
    {
        $first = true;

        foreach ($columns as $column) {
            foreach ([true, 1, '1', 'true', 'sim', 'yes', 'on'] as $value) {
                $method = $first ? 'where' : 'orWhere';
                $query->{$method}($column, $value);
                $first = false;
            }
        }
    }

    private function applyBlankConditions(Builder $query, array $columns): void
    {
        $first = true;

        foreach ($columns as $column) {
            $method = $first ? 'whereNull' : 'orWhereNull';
            $query->{$method}($column);
            $query->orWhere($column, '');

            $first = false;
        }
    }

    private function formValue(Campista $campista, string $key): mixed
    {
        return data_get($campista->form_data ?? [], $key);
    }

    private function shirtLabel(mixed $size, mixed $otherSize): string
    {
        if ($this->isBlank($size)) {
            return 'Sem tamanho';
        }

        if ($size !== 'O') {
            return (string) $size;
        }

        return $this->isBlank($otherSize)
            ? 'Outros'
            : 'Outros: '.$otherSize;
    }

    private function communityLabel(mixed $paroquia, mixed $comunidade): string
    {
        if ($this->isBlank($paroquia) || $this->isBlank($comunidade)) {
            return 'Sem comunidade';
        }

        return ParishCommunityLabels::combinedLabel($paroquia, $comunidade, short: true);
    }

    private function ageBucket(mixed $date): string
    {
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
