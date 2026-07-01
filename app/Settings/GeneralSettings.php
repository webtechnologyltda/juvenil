<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $telefone_atendente;

    public ?int $valor_acampamento;

    public int $valor_equipe_trabalho_interna;

    public int $valor_equipe_trabalho_externa;

    public ?int $idade_minima;

    public ?int $idade_maxima;

    public ?int $qtd_max_vagas;

    public ?int $qtd_max_vagas_feminino;

    public ?int $qtd_max_vagas_masculino;

    public int $waitlist_invitation_hours;

    public ?\DateTime $data_inicio_inscricoes;

    public ?\DateTime $data_final_inscricoes;

    public ?string $pix_copia_cola;

    public ?string $pix_qr_code;

    public ?string $termo_responsabilidade;

    public ?array $atendentes;

    public int $liberacao_inscricoes_status;

    public int $liberacao_inscricoes_equipe_trabalho_status;

    public ?string $liberacao_inscricoes_bloco;

    public static function group(): string
    {
        return 'general';
    }
}
