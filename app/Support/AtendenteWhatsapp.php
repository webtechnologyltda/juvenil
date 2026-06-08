<?php

namespace App\Support;

class AtendenteWhatsapp
{
    public const DEFAULT_MESSAGE = 'Olá tenho uma dúvida sobre o Acampamento Juvenil, consegue me ajudar?';

    public const PURPOSE_DUVIDAS = 'duvidas';

    public const PURPOSE_COMPROVANTE = 'comprovante';

    public const PURPOSE_NECESSIDADE_ESPECIFICA = 'necessidade_especifica';

    private const PURPOSE_MESSAGES = [
        self::PURPOSE_DUVIDAS => self::DEFAULT_MESSAGE,
        self::PURPOSE_COMPROVANTE => 'Olá, realizei minha inscrição no Acampamento Juvenil e quero enviar o comprovante de pagamento.',
        self::PURPOSE_NECESSIDADE_ESPECIFICA => 'Olá, tenho uma necessidade específica sobre o Acampamento Juvenil e preciso de ajuda.',
    ];

    public static function url(?string $phone, string $message = self::DEFAULT_MESSAGE): ?string
    {
        $number = self::number($phone);

        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    public static function number(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '55') ? $digits : '55'.$digits;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $attendants
     * @return array<int, array<string, string|null>>
     */
    public static function forPurpose(?array $attendants, string $purpose, ?string $legacyPhone = null, ?int $limit = null): array
    {
        $contacts = collect(self::normalize($attendants))
            ->filter(fn (array $attendant): bool => $attendant['tipo'] === $purpose)
            ->values();

        if ($contacts->isEmpty() && filled($legacyPhone)) {
            $contacts = collect([self::legacyContact($legacyPhone, $purpose)]);
        }

        if ($limit !== null) {
            $contacts = $contacts->take($limit);
        }

        return $contacts->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $attendants
     * @return array<string, string|null>|null
     */
    public static function firstForPurpose(
        ?array $attendants,
        string $purpose,
        ?string $legacyPhone = null,
        bool $fallbackToAny = true,
    ): ?array {
        $contacts = self::forPurpose($attendants, $purpose, $legacyPhone, 1);

        if ($contacts !== []) {
            return $contacts[0];
        }

        if (! $fallbackToAny) {
            return null;
        }

        return self::normalize($attendants)[0] ?? null;
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $attendants
     * @return array<int, array<string, string|null>>
     */
    public static function normalize(?array $attendants): array
    {
        return collect($attendants ?? [])
            ->map(fn (array $attendant): ?array => self::normalizeContact($attendant))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $attendant
     * @return array<string, string|null>|null
     */
    private static function normalizeContact(array $attendant): ?array
    {
        $phone = $attendant['telefone'] ?? $attendant['phone'] ?? null;
        $number = self::number(is_string($phone) ? $phone : null);

        if ($number === null) {
            return null;
        }

        $purpose = self::normalizePurpose($attendant['tipo'] ?? null);
        $name = filled($attendant['nome'] ?? null) ? (string) $attendant['nome'] : 'Atendente';
        $observation = filled($attendant['observacao'] ?? null) ? (string) $attendant['observacao'] : null;

        return [
            'nome' => $name,
            'telefone' => is_string($phone) ? $phone : null,
            'numero' => $number,
            'tipo' => $purpose,
            'observacao' => $observation,
            'whatsapp_url' => 'https://wa.me/'.$number.'?text='.rawurlencode(self::messageForPurpose($purpose)),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    private static function legacyContact(string $phone, string $purpose): array
    {
        return self::normalizeContact([
            'nome' => 'Atendente',
            'telefone' => $phone,
            'tipo' => $purpose,
        ]);
    }

    private static function normalizePurpose(mixed $purpose): string
    {
        if (in_array($purpose, [
            self::PURPOSE_DUVIDAS,
            self::PURPOSE_COMPROVANTE,
            self::PURPOSE_NECESSIDADE_ESPECIFICA,
        ], true)) {
            return (string) $purpose;
        }

        return self::PURPOSE_DUVIDAS;
    }

    private static function messageForPurpose(string $purpose): string
    {
        return self::PURPOSE_MESSAGES[$purpose] ?? self::DEFAULT_MESSAGE;
    }
}
