<?php

namespace App\Support;

use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;

class RegistrationAgeLimits
{
    public function __construct(
        private readonly ?int $minimumAge,
        private readonly ?int $maximumAge,
    ) {}

    public static function fromSettings(GeneralSettings $settings): self
    {
        return new self(
            minimumAge: $settings->idade_minima,
            maximumAge: $settings->idade_maxima,
        );
    }

    public function allows(?string $birthdate): bool
    {
        return $this->violationMessage($birthdate) === null;
    }

    public function violationMessage(?string $birthdate): ?string
    {
        $age = $this->resolveAge($birthdate);

        if ($age === null) {
            return 'Informe uma data de nascimento válida para concluir a inscrição.';
        }

        $minimumAge = $this->activeLimit($this->minimumAge);

        if ($minimumAge !== null && $age < $minimumAge) {
            return "As inscrições estão liberadas apenas para campistas com {$minimumAge} anos ou mais.";
        }

        $maximumAge = $this->activeLimit($this->maximumAge);

        if ($maximumAge !== null && $age > $maximumAge) {
            return "As inscrições estão liberadas apenas para campistas com até {$maximumAge} anos.";
        }

        return null;
    }

    private function activeLimit(?int $limit): ?int
    {
        return $limit !== null && $limit > 0 ? $limit : null;
    }

    private function resolveAge(?string $birthdate): ?int
    {
        if (blank($birthdate)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('d/m/Y', $birthdate);
        } catch (\Throwable) {
            return null;
        }

        if ($date === false || $date->format('d/m/Y') !== $birthdate) {
            return null;
        }

        return $date->age;
    }
}
