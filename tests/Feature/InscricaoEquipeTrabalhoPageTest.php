<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('redirects the equipe de trabalho registration page to campista registration', function () {
    $this->withoutVite();

    foreach ([
        'telefone_atendente' => null,
        'valor_acampamento' => null,
        'idade_minima' => 0,
        'idade_maxima' => 0,
        'qtd_max_vagas' => null,
        'qtd_max_vagas_feminino' => null,
        'qtd_max_vagas_masculino' => null,
        'data_inicio_inscricoes' => null,
        'data_final_inscricoes' => null,
        'pix_copia_cola' => null,
        'pix_qr_code' => null,
        'liberacao_inscricoes_status' => LiberacaoInscricoesStatusEnum::LIBERADO->value,
        'liberacao_inscricoes_equipe_trabalho_status' => LiberacaoInscricoesEquipeTrabalhoStatusEnum::LIBERADO->value,
        'liberacao_inscricoes_bloco' => null,
    ] as $name => $payload) {
        DB::table('settings')->updateOrInsert(
            [
                'group' => 'general',
                'name' => $name,
            ],
            [
                'payload' => json_encode($payload),
            ],
        );
    }

    $this->get(route('inscricao-equipe-trabalho'))
        ->assertRedirect(route('campista'));
});
