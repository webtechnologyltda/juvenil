<?php

namespace App\Support;

use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;
use DateTimeInterface;

class CampistaRegistrationAvailability
{
    private const SEX_LABELS = [
        'M' => 'masculino',
        'F' => 'feminino',
    ];

    private ?int $activeCount = null;

    private ?CarbonImmutable $now = null;

    /**
     * @var array<string, int>
     */
    private array $activeSexCounts = [];

    /**
     * @param  array<string, mixed>  $settings
     */
    private function __construct(private readonly array $settings) {}

    /**
     * @param  array<string, mixed>|GeneralSettings  $settings
     */
    public static function fromSettings(array|GeneralSettings $settings): self
    {
        return new self($settings instanceof GeneralSettings ? $settings->toArray() : $settings);
    }

    public function registrationClosedByCapacity(): bool
    {
        if ($this->totalLimitReached()) {
            return true;
        }

        return $this->sexLimitReached('M') && $this->sexLimitReached('F');
    }

    public function registrationOpen(): bool
    {
        return $this->manualStatus() === LiberacaoInscricoesStatusEnum::LIBERADO
            && ! $this->registrationStartsInFuture()
            && ! $this->registrationEnded()
            && ! $this->registrationClosedByCapacity();
    }

    public function registrationStartsInFuture(): bool
    {
        $startsAt = $this->startsAt();

        return $startsAt !== null && $this->now()->lt($startsAt);
    }

    public function registrationEnded(): bool
    {
        $endsAt = $this->endsAt();

        return $endsAt !== null && $this->now()->gt($endsAt);
    }

    public function startsAtIso(): ?string
    {
        return $this->startsAt()?->toIso8601String();
    }

    public function endsAtIso(): ?string
    {
        return $this->endsAt()?->toIso8601String();
    }

    public function startsAtDisplay(): ?string
    {
        return $this->startsAt()?->format('d/m/Y H:i');
    }

    public function endsAtDisplay(): ?string
    {
        return $this->endsAt()?->format('d/m/Y H:i');
    }

    public function siteDetailsMessage(): string
    {
        $startsAt = $this->startsAt();

        if ($startsAt === null) {
            return 'Inscrições conforme disponibilidade de vagas.';
        }

        return 'Inscrições a partir de '.$this->siteDateTimeDisplay($startsAt).'.';
    }

    public function totalCapacity(): ?int
    {
        if ($this->hasSexSpecificLimits()) {
            $maleLimit = $this->sexLimit('M');
            $femaleLimit = $this->sexLimit('F');

            if ($maleLimit === null || $femaleLimit === null) {
                return null;
            }

            return $maleLimit + $femaleLimit;
        }

        if (($totalLimit = $this->totalLimit()) !== null) {
            return $totalLimit;
        }

        return null;
    }

    public function remainingSlots(): ?int
    {
        $capacity = $this->totalCapacity();

        if ($capacity === null) {
            return null;
        }

        return max(0, $capacity - $this->activeCount());
    }

    public function activeRegistrations(): int
    {
        return $this->activeCount();
    }

    public function availableSlotsMessage(): ?string
    {
        $capacity = $this->totalCapacity();
        $remaining = $this->remainingSlots();

        if ($capacity === null || $remaining === null) {
            return null;
        }

        $label = $remaining === 1 ? 'vaga disponível' : 'vagas disponíveis';

        return "{$remaining} {$label} de {$capacity}";
    }

    public function activeRegistrationsMessage(): string
    {
        $activeRegistrations = $this->activeRegistrations();
        $label = $activeRegistrations === 1 ? 'inscrição ativa' : 'inscrições ativas';

        return "{$activeRegistrations} {$label}";
    }

    public function unavailableRegistrationMessage(): string
    {
        if ($this->manualStatus() === LiberacaoInscricoesStatusEnum::TRANCADO) {
            return 'As inscrições ainda não foram liberadas.';
        }

        if ($this->manualStatus() === LiberacaoInscricoesStatusEnum::ENCERRADO) {
            return 'As inscrições do Acampamento Juvenil estão encerradas.';
        }

        if ($this->registrationStartsInFuture()) {
            return 'As inscrições começam em '.$this->startsAtDisplay().'.';
        }

        if ($this->registrationEnded()) {
            return 'O período de inscrições encerrou em '.$this->endsAtDisplay().'.';
        }

        if ($this->registrationClosedByCapacity()) {
            return 'As inscrições foram encerradas pelo número de vagas preenchidas.';
        }

        return 'As inscrições não estão disponíveis no momento.';
    }

    public function sexHasVacancy(?string $sex): bool
    {
        if (! array_key_exists((string) $sex, self::SEX_LABELS)) {
            return false;
        }

        return ! $this->totalLimitReached()
            && ! $this->sexLimitReached((string) $sex);
    }

    public function unavailableSexMessage(): ?string
    {
        $labels = $this->unavailableSexLabels();

        if ($labels === []) {
            return null;
        }

        return 'Não há vagas disponíveis para o sexo '.$this->formatSexLabels($labels).'.';
    }

    public function unavailableSelectedSexMessage(?string $sex): string
    {
        if ($this->totalLimitReached()) {
            return 'As inscrições foram encerradas pelo número de vagas preenchidas.';
        }

        return 'Não há vagas disponíveis para o sexo '.(self::SEX_LABELS[(string) $sex] ?? 'selecionado').'.';
    }

    public function totalLimitReached(): bool
    {
        if ($this->hasSexSpecificLimits()) {
            return false;
        }

        $limit = $this->totalLimit();

        return $limit !== null && $this->activeCount() >= $limit;
    }

    public function sexLimitReached(string $sex): bool
    {
        $limit = $this->sexLimit($sex);

        return $limit !== null && $this->activeCountForSex($sex) >= $limit;
    }

    /**
     * @return array<int, string>
     */
    private function unavailableSexLabels(): array
    {
        return collect(array_keys(self::SEX_LABELS))
            ->filter(fn (string $sex): bool => $this->sexLimitReached($sex))
            ->map(fn (string $sex): string => self::SEX_LABELS[$sex])
            ->values()
            ->all();
    }

    private function activeCount(): int
    {
        return $this->activeCount ??= Campista::query()
            ->where('status', '<>', StatusInscricao::Cancelado->value)
            ->count();
    }

    private function activeCountForSex(string $sex): int
    {
        if (array_key_exists($sex, $this->activeSexCounts)) {
            return $this->activeSexCounts[$sex];
        }

        return $this->activeSexCounts[$sex] = Campista::query()
            ->where('status', '<>', StatusInscricao::Cancelado->value)
            ->get(['form_data'])
            ->filter(fn (Campista $campista): bool => data_get($campista->form_data, 'sexo') === $sex)
            ->count();
    }

    private function manualStatus(): LiberacaoInscricoesStatusEnum
    {
        $status = $this->settings['liberacao_inscricoes_status'] ?? null;

        if ($status instanceof LiberacaoInscricoesStatusEnum) {
            return $status;
        }

        return LiberacaoInscricoesStatusEnum::tryFrom((int) $status)
            ?? LiberacaoInscricoesStatusEnum::ENCERRADO;
    }

    private function startsAt(): ?CarbonImmutable
    {
        return $this->date('data_inicio_inscricoes');
    }

    private function endsAt(): ?CarbonImmutable
    {
        return $this->date('data_final_inscricoes');
    }

    private function now(): CarbonImmutable
    {
        return $this->now ??= CarbonImmutable::instance(now());
    }

    private function sexLimit(string $sex): ?int
    {
        return match ($sex) {
            'M' => $this->limit('qtd_max_vagas_masculino'),
            'F' => $this->limit('qtd_max_vagas_feminino'),
            default => null,
        };
    }

    private function totalLimit(): ?int
    {
        return $this->limit('qtd_max_vagas');
    }

    private function hasSexSpecificLimits(): bool
    {
        return $this->sexLimit('M') !== null
            || $this->sexLimit('F') !== null;
    }

    private function limit(string $key): ?int
    {
        $value = $this->settings[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        $limit = (int) $value;

        return $limit > 0 ? $limit : null;
    }

    private function date(string $key): ?CarbonImmutable
    {
        $value = $this->settings[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        return CarbonImmutable::parse((string) $value);
    }

    private function siteDateTimeDisplay(CarbonImmutable $date): string
    {
        return sprintf(
            '%s de %s, às %s',
            $date->format('d'),
            $this->monthName($date),
            $this->hourDisplay($date),
        );
    }

    private function monthName(CarbonImmutable $date): string
    {
        return [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ][$date->month];
    }

    private function hourDisplay(CarbonImmutable $date): string
    {
        if ((int) $date->format('i') === 0) {
            return $date->format('G').'h';
        }

        return $date->format('G\hi');
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function formatSexLabels(array $labels): string
    {
        if (count($labels) === 1) {
            return $labels[0];
        }

        $last = array_pop($labels);

        return implode(', ', $labels).' e '.$last;
    }
}
