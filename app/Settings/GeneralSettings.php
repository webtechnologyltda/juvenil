<?php

namespace App\Settings;

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?string $telefone_atendente;
    public ?int $qtd_max_vagas;
    public ?int $qtd_max_vagas_feminino;
    public ?int $qtd_max_vagas_masculino;
    public ?\DateTime $data_inicio_inscricoes;
    public ?\DateTime $data_final_inscricoes;
    public int $liberacao_inscricoes_status;
    public int $liberacao_inscricoes_equipe_trabalho_status;
    public ?string $liberacao_inscricoes_bloco;

    public static function group(): string
    {
        return 'general';
    }
}
