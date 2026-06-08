<?php

namespace App\Support\Campistas;

final class ParishCommunityLabels
{
    private const PARISHES = [
        0 => [
            'full' => 'Paróquia São Domingos e Nossa Senhora do Carmo',
            'short' => 'São Domingos e N. Sra. do Carmo',
        ],
        1 => [
            'full' => 'Paróquia Santa Luzia',
            'short' => 'Santa Luzia',
        ],
        2 => [
            'full' => 'Outra paróquia',
            'short' => 'Outra paróquia',
        ],
    ];

    private const COMMUNITIES = [
        0 => [
            ['full' => 'Comunidade Matriz de São Domingos e Nossa Senhora do Carmo', 'short' => 'Matriz São Domingos e N. Sra. do Carmo'],
            ['full' => 'Comunidade Nossa Senhora das Graças', 'short' => 'Nossa Senhora das Graças'],
            ['full' => 'Comunidade São Paulo', 'short' => 'São Paulo'],
            ['full' => 'Comunidade Nossa Senhora do Rosário', 'short' => 'Nossa Senhora do Rosário'],
            ['full' => 'Comunidade Imaculado Coração de Maria', 'short' => 'Imaculado Coração de Maria'],
        ],
        1 => [
            ['full' => 'Comunidade Santa Luzia - Machados', 'short' => 'Santa Luzia - Machados'],
            ['full' => 'Comunidade Santa Teresinha', 'short' => 'Santa Teresinha'],
            ['full' => 'Comunidade São Francisco', 'short' => 'São Francisco'],
            ['full' => 'Comunidade Sagrado Coração', 'short' => 'Sagrado Coração'],
            ['full' => 'Comunidade Nossa Senhora de Fátima', 'short' => 'Nossa Senhora de Fátima'],
            ['full' => 'Comunidade Santo Agostinho', 'short' => 'Santo Agostinho'],
            ['full' => 'Comunidade São José', 'short' => 'São José'],
            ['full' => 'Comunidade Nossa Senhora Aparecida', 'short' => 'Nossa Senhora Aparecida'],
        ],
    ];

    public static function parishLabel(mixed $parish, bool $short = false): ?string
    {
        if (blank($parish)) {
            return null;
        }

        $labelType = $short ? 'short' : 'full';

        return self::PARISHES[(int) $parish][$labelType] ?? (string) $parish;
    }

    public static function parishOptions(bool $short = false): array
    {
        $labelType = $short ? 'short' : 'full';

        return collect(self::PARISHES)
            ->map(fn (array $labels): string => $labels[$labelType])
            ->all();
    }

    public static function communityLabel(mixed $parish, mixed $community, bool $short = false): ?string
    {
        if (blank($community)) {
            return null;
        }

        if (! is_numeric($community)) {
            return trim((string) $community);
        }

        $labelType = $short ? 'short' : 'full';

        return self::COMMUNITIES[(int) $parish][(int) $community][$labelType] ?? (string) $community;
    }

    public static function communityOptions(mixed $parish, bool $short = false): array
    {
        if (blank($parish) || ! is_numeric($parish)) {
            return [];
        }

        $labelType = $short ? 'short' : 'full';

        return collect(self::COMMUNITIES[(int) $parish] ?? [])
            ->map(fn (array $labels): string => $labels[$labelType])
            ->all();
    }

    public static function combinedLabel(mixed $parish, mixed $community, bool $short = false, string $emptyLabel = 'Sem comunidade'): string
    {
        $parishLabel = self::parishLabel($parish, $short);
        $communityLabel = self::communityLabel($parish, $community, $short);

        if ($parishLabel === null || $communityLabel === null) {
            return $emptyLabel;
        }

        return $parishLabel.' / '.$communityLabel;
    }
}
