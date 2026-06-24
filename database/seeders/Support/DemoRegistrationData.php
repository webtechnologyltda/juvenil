<?php

namespace Database\Seeders\Support;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\TipoEquipeTrabalho;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

class DemoRegistrationData
{
    public const CAMPISTA_TOTAL = 120;

    public const EQUIPE_TRABALHO_TOTAL = 200;

    public const DEMO_OBSERVATION = 'Dados demonstrativos para testes e painel operacional.';

    public const TRIBE_COLORS = [
        'Azul',
        'Vermelha',
        'Verde',
        'Amarela',
        'Roxa',
        'Laranja',
        'Rosa',
        'Branca',
        'Preta',
        'Cinza',
    ];

    private const FIRST_NAMES = [
        'Ana',
        'Bruno',
        'Camila',
        'Daniel',
        'Eduarda',
        'Felipe',
        'Gabriela',
        'Henrique',
        'Isabela',
        'João',
        'Larissa',
        'Mateus',
        'Natália',
        'Otávio',
        'Patrícia',
        'Rafael',
        'Sofia',
        'Tiago',
        'Vitória',
        'William',
    ];

    private const SURNAMES = [
        'Souza',
        'Silva',
        'Oliveira',
        'Santos',
        'Pereira',
        'Costa',
        'Rodrigues',
        'Almeida',
        'Ferreira',
        'Mendes',
    ];

    private const NAVEGANTES_ADDRESSES = [
        ['cep' => '88370-100', 'rua' => 'Rua Prefeito José Juvenal Mafra', 'bairro' => 'Centro'],
        ['cep' => '88372-000', 'rua' => 'Avenida Prefeito Cirino Adolfo Cabral', 'bairro' => 'Gravatá'],
        ['cep' => '88375-000', 'rua' => 'Rua Manoel Evaldo Müller', 'bairro' => 'Machados'],
        ['cep' => '88370-450', 'rua' => 'Rua João Emílio', 'bairro' => 'São Domingos'],
        ['cep' => '88371-620', 'rua' => 'Rua Vereador Nereu Liberato Nunes', 'bairro' => 'São Paulo'],
        ['cep' => '88370-660', 'rua' => 'Rua Maria Leonor da Cunha Rebelo', 'bairro' => 'Nossa Senhora das Graças'],
        ['cep' => '88373-120', 'rua' => 'Rua Orlando Ferreira', 'bairro' => 'Meia Praia'],
        ['cep' => '88374-350', 'rua' => 'Rua Onório Bortolato', 'bairro' => 'Pedreiras'],
        ['cep' => '88376-000', 'rua' => 'Rua José Francisco Laurindo', 'bairro' => 'Escalvados'],
        ['cep' => '88377-000', 'rua' => 'Rua José Silvestre Toledo dos Santos', 'bairro' => 'Volta Grande'],
    ];

    private const SHIRT_SIZES = ['14', 'PP', 'P', 'M', 'G', 'GG', 'EG', 'X1', 'O'];

    public static function campistaAttributes(int $index, ?int $triboId = null): array
    {
        $status = self::campistaStatus($index);
        $createdAt = self::createdAt($index);

        return [
            'nome' => self::personName($index),
            'avatar_url' => self::campistaAvatarPath($index),
            'form_data' => self::campistaFormData($index, $status),
            'status' => $status->value,
            'forma_pagamento' => self::campistaPaymentMethod($index, $status)?->value,
            'dia_pagamento' => $status === StatusInscricao::Pago ? $createdAt->addDay() : null,
            'observacoes' => self::DEMO_OBSERVATION,
            'presenca' => $status === StatusInscricao::Pago && ($index % 3) === 0,
            'tribo_id' => $triboId,
            'user_id' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public static function equipeTrabalhoAttributes(int $index, ?int $triboId = null): array
    {
        $createdAt = self::createdAt($index);

        return [
            'nome' => self::personName($index, 'Equipe'),
            'avatar_url' => self::equipeTrabalhoAvatarPath($index),
            'data_form' => self::equipeTrabalhoFormData($index),
            'status' => self::equipeTrabalhoStatus($index)->value,
            'tribo_id' => $triboId,
            'descricao' => self::DEMO_OBSERVATION,
            'tipo_equipe' => self::equipeTrabalhoType($index)->value,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    private static function campistaFormData(int $index, StatusInscricao $status): array
    {
        $address = self::address($index);
        [$paroquia, $comunidade] = self::parishAndCommunity($index);
        $takesMedicine = ($index % 5) === 0;
        $hasRecommendation = ($index % 7) === 0;
        $shirtSize = self::shirtSize($index);
        $hasRetreat = ($index % 4) === 0;
        $hasRelative = ($index % 6) === 0;

        return [
            'data_nacimento' => self::birthDate($index),
            'sexo' => self::sex($index),
            'altura' => (string) (155 + ($index % 35)),
            'peso' => (string) (48 + ($index % 42)),
            'rede_social' => sprintf('@campista%03d', $index),
            'telefone_campista' => self::phone($index),
            'telefone_reponsavel_1' => self::phone($index, 100),
            'telefone_reponsavel_nome_1' => self::personName($index + 30, 'Responsável'),
            'cep' => $address['cep'],
            'rua' => $address['rua'],
            'numero' => (string) (100 + $index),
            'ponto_referencia' => 'Casa '.(100 + $index).', fundos',
            'bairro' => $address['bairro'],
            'cidade' => 'Navegantes',
            'estado' => 'SC',
            'paroquia' => $paroquia,
            'comunidade' => $comunidade,
            'toma_remedio' => $takesMedicine,
            'remedio' => $takesMedicine ? 'Dipirona 500mg às 20h' : 'Não se aplica',
            'tem_recomendacao' => $hasRecommendation,
            'recomendacao' => $hasRecommendation ? 'Evitar amendoim e avisar a enfermaria.' : 'Não se aplica',
            'tamanho_camiseta' => $shirtSize,
            'tamanho_camiseta_outro' => $shirtSize === 'O' ? 'XGG' : 'Não se aplica',
            'ja_participou_retiro' => $hasRetreat,
            'retiro_que_participou' => $hasRetreat ? ['Acampamento Juvenil 2024'] : [],
            'algum_parente' => $hasRelative,
            'algum_parente_participante' => $hasRelative ? [self::personName($index + 90)] : [],
            'declaro' => ($index % 2) === 1,
            'aceite_termo_inscricao' => true,
            'aceitar_politica_privacidade' => true,
            'comprovante_nome' => $status === StatusInscricao::Pago ? sprintf('Comprovante campista %03d', $index) : 'Aguardando pagamento',
            'comprovante' => $status === StatusInscricao::Pago ? [sprintf('comprovantes/campista-%03d.pdf', $index)] : [],
        ];
    }

    private static function equipeTrabalhoFormData(int $index): array
    {
        $address = self::address($index);
        $shirtSize = self::shirtSize($index + 2);
        $hasRetreat = ($index % 3) === 0;

        return [
            'data_nacimento' => self::birthDate($index + 80, minimumYear: 1978, yearSpan: 28),
            'sexo' => self::sex($index),
            'rede_social' => sprintf('@equipe%03d', $index),
            'telefone' => self::phone($index, 300),
            'reponsavel_nome' => self::personName($index + 120, 'Contato'),
            'reponsavel_telefone' => self::phone($index, 400),
            'cep' => $address['cep'],
            'rua' => $address['rua'],
            'numero' => (string) (300 + $index),
            'ponto_referencia' => 'Apartamento '.(($index % 12) + 1),
            'bairro' => $address['bairro'],
            'cidade' => 'Navegantes',
            'estado' => 'SC',
            'ja_participou_retiro' => $hasRetreat,
            'retiro_que_participou' => $hasRetreat ? ['Equipe de Trabalho 2023'] : [],
            'pode_missas_diarias' => ($index % 4) !== 0,
            'tamanho_camiseta' => $shirtSize,
            'tamanho_camiseta_outro' => $shirtSize === 'O' ? 'XGG' : 'Não se aplica',
            'servir_no_acampamento' => ($index % 5) !== 0,
        ];
    }

    private static function campistaStatus(int $index): StatusInscricao
    {
        return match ($index % 12) {
            0 => StatusInscricao::Cancelado,
            1, 2, 3 => StatusInscricao::Pendente,
            default => StatusInscricao::Pago,
        };
    }

    private static function equipeTrabalhoStatus(int $index): StatusInscricaoEquipeTrabalho
    {
        return match ($index % 10) {
            0 => StatusInscricaoEquipeTrabalho::Cancelado,
            1, 2 => StatusInscricaoEquipeTrabalho::Pendente,
            default => StatusInscricaoEquipeTrabalho::Aprovado,
        };
    }

    private static function equipeTrabalhoType(int $index): TipoEquipeTrabalho
    {
        return ($index % 4) === 0
            ? TipoEquipeTrabalho::Externa
            : TipoEquipeTrabalho::Interna;
    }

    private static function campistaPaymentMethod(int $index, StatusInscricao $status): ?FormaPagamento
    {
        if ($status === StatusInscricao::Pendente) {
            return FormaPagamento::NaoPago;
        }

        if ($status === StatusInscricao::Cancelado) {
            return null;
        }

        return match ($index % 3) {
            0 => FormaPagamento::Cartao,
            1 => FormaPagamento::Pix,
            default => FormaPagamento::Dinheiro,
        };
    }

    private static function personName(int $index, ?string $middle = null): string
    {
        $firstName = self::FIRST_NAMES[($index - 1) % count(self::FIRST_NAMES)];
        $surname = self::SURNAMES[(int) floor(($index - 1) / count(self::FIRST_NAMES)) % count(self::SURNAMES)];
        $number = str_pad((string) $index, 3, '0', STR_PAD_LEFT);

        return trim(implode(' ', array_filter([$firstName, $surname, $middle, $number])));
    }

    private static function address(int $index): array
    {
        return self::NAVEGANTES_ADDRESSES[($index - 1) % count(self::NAVEGANTES_ADDRESSES)];
    }

    private static function parishAndCommunity(int $index): array
    {
        return match ($index % 3) {
            0 => [0, ($index - 1) % 5],
            1 => [1, ($index - 1) % 8],
            default => [2, 'Comunidade São Pedro - Navegantes'],
        };
    }

    private static function shirtSize(int $index): string
    {
        return self::SHIRT_SIZES[($index - 1) % count(self::SHIRT_SIZES)];
    }

    private static function sex(int $index): string
    {
        return ($index % 2) === 0 ? 'M' : 'F';
    }

    public static function ensureCampistaAvatarFiles(): void
    {
        foreach (range(1, self::CAMPISTA_TOTAL) as $index) {
            self::putDemoAvatar(self::campistaAvatarPath($index), $index);
        }
    }

    public static function ensureEquipeTrabalhoAvatarFiles(): void
    {
        foreach (range(1, self::EQUIPE_TRABALHO_TOTAL) as $index) {
            self::putDemoAvatar(self::equipeTrabalhoAvatarPath($index), $index + self::CAMPISTA_TOTAL);
        }
    }

    private static function campistaAvatarPath(int $index): string
    {
        return sprintf('foto-formulario/campista-%03d.png', $index);
    }

    private static function equipeTrabalhoAvatarPath(int $index): string
    {
        return sprintf('foto-formulario-equipe-trabalho/equipe-%03d.png', $index);
    }

    private static function putDemoAvatar(string $path, int $index): void
    {
        Storage::disk('public')->put($path, self::demoAvatarPng($index));
    }

    private static function demoAvatarPng(int $index): string
    {
        $size = 96;
        $image = imagecreatetruecolor($size, $size);

        imagealphablending($image, true);
        imagesavealpha($image, true);

        [$red, $green, $blue] = self::avatarColor($index);
        [$accentRed, $accentGreen, $accentBlue] = self::avatarColor($index + 7);

        $background = imagecolorallocate($image, $red, $green, $blue);
        $accent = imagecolorallocate($image, $accentRed, $accentGreen, $accentBlue);
        $light = imagecolorallocate($image, 245, 251, 253);
        $dark = imagecolorallocate($image, 5, 47, 53);

        imagefilledrectangle($image, 0, 0, $size, $size, $background);
        imagefilledellipse($image, 48, 32, 34, 34, $light);
        imagefilledrectangle($image, 24, 56, 72, 95, $light);
        imagefilledpolygon($image, [0, 96, 96, 0, 96, 96], 3, $accent);
        imagefilledrectangle($image, 12, 76, 84, 95, $dark);
        imagestring($image, 5, 30, 78, str_pad((string) ($index % 1000), 3, '0', STR_PAD_LEFT), $light);

        ob_start();
        imagepng($image);
        $contents = (string) ob_get_clean();

        imagedestroy($image);

        return $contents;
    }

    private static function avatarColor(int $index): array
    {
        $palette = [
            [244, 107, 18],
            [5, 94, 110],
            [157, 219, 239],
            [103, 57, 214],
            [16, 126, 98],
            [208, 65, 90],
        ];

        return $palette[($index - 1) % count($palette)];
    }

    private static function birthDate(int $index, int $minimumYear = 1998, int $yearSpan = 14): string
    {
        $day = str_pad((string) (1 + (($index - 1) % 28)), 2, '0', STR_PAD_LEFT);
        $month = str_pad((string) (1 + (($index - 1) % 12)), 2, '0', STR_PAD_LEFT);
        $year = $minimumYear + (($index - 1) % $yearSpan);

        return "{$day}/{$month}/{$year}";
    }

    private static function phone(int $index, int $offset = 0): string
    {
        $seed = $index + $offset;

        return sprintf('(47) 9 %04d-%04d', 8000 + ($seed % 1000), 1000 + (($seed * 7) % 9000));
    }

    private static function createdAt(int $index): CarbonImmutable
    {
        return CarbonImmutable::create(2026, 6, 1, 9, 0, 0)
            ->addDays(($index - 1) % 20)
            ->addMinutes($index);
    }
}
