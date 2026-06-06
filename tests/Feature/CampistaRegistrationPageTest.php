<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('renders the main page with the campista registration form only', function () {
    seedGeneralRegistrationSettings();

    $this->withoutVite();

    $this->get('/')
        ->assertOk()
        ->assertSee('Inscrição')
        ->assertSee('Acampamento Juvenil')
        ->assertSee('22 a 26 de Julho')
        ->assertSee('acampamento-juvenil-divulgacao')
        ->assertSee('Comprar')
        ->assertSee('filament-registration-shell')
        ->assertDontSee('Trekking')
        ->assertDontSee('Offset')
        ->assertDontSee('Inscrição para equipe de trabalho')
        ->assertDontSee('Increver-se para Trabalhar');
});

it('renders the campista registration route', function () {
    seedGeneralRegistrationSettings();

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Inscrição')
        ->assertSee('Comprar');
});

function seedGeneralRegistrationSettings(): void
{
    foreach ([
        'telefone_atendente' => '(47) 9 9999-9999',
        'qtd_max_vagas' => null,
        'qtd_max_vagas_feminino' => null,
        'qtd_max_vagas_masculino' => null,
        'data_inicio_inscricoes' => null,
        'data_final_inscricoes' => null,
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
}
