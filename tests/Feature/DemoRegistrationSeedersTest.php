<?php

use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\Tribo;
use Database\Seeders\CampistaSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\EquipeTrabalhoSeeder;
use Database\Seeders\TriboSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds deterministic complete registration data for dashboard validation', function () {
    $this->seed(TriboSeeder::class);
    $this->seed(CampistaSeeder::class);
    $this->seed(EquipeTrabalhoSeeder::class);

    expect(config('app.faker_locale'))->toBe('pt_BR')
        ->and(Tribo::query()->pluck('cor')->all())->toBe([
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
        ])
        ->and(Campista::query()->count())->toBe(120)
        ->and(EquipeTrabalho::query()->count())->toBe(200);

    $firstCampista = Campista::query()->oldest('id')->firstOrFail();
    $firstEquipe = EquipeTrabalho::query()->oldest('id')->firstOrFail();

    expect($firstCampista->nome)->toBe('Ana Souza 001')
        ->and($firstCampista->form_data)->toHaveKeys(campistaSeederRequiredFormKeys())
        ->and($firstCampista->form_data['cidade'])->toBe('Navegantes')
        ->and($firstCampista->form_data['estado'])->toBe('SC')
        ->and($firstCampista->form_data['cep'])->toStartWith('883')
        ->and($firstCampista->avatar_url)->toStartWith('foto-formulario/')
        ->and($firstCampista->tribo_id)->not->toBeNull()
        ->and($firstEquipe->nome)->toBe('Ana Souza Equipe 001')
        ->and($firstEquipe->data_form)->toHaveKeys(equipeTrabalhoSeederRequiredFormKeys())
        ->and($firstEquipe->data_form['cidade'])->toBe('Navegantes')
        ->and($firstEquipe->data_form['estado'])->toBe('SC')
        ->and($firstEquipe->data_form['cep'])->toStartWith('883')
        ->and($firstEquipe->avatar_url)->toStartWith('foto-formulario-equipe-trabalho/');

    expect(collect($firstCampista->form_data)->except(['comprovante'])->filter(fn ($value): bool => $value === null)->all())->toBe([])
        ->and(collect($firstEquipe->data_form)->filter(fn ($value): bool => $value === null)->all())->toBe([]);

    expect(Campista::query()->where('status', StatusInscricao::Pendente->value)->count())->toBeGreaterThan(0)
        ->and(Campista::query()->where('status', StatusInscricao::Pago->value)->count())->toBeGreaterThan(0)
        ->and(Campista::query()->where('status', StatusInscricao::Cancelado->value)->count())->toBeGreaterThan(0)
        ->and(Campista::query()->where('presenca', true)->count())->toBeGreaterThan(0)
        ->and(Campista::query()->where('presenca', false)->count())->toBeGreaterThan(0)
        ->and(EquipeTrabalho::query()->where('status', StatusInscricaoEquipeTrabalho::Pendente->value)->count())->toBeGreaterThan(0)
        ->and(EquipeTrabalho::query()->where('status', StatusInscricaoEquipeTrabalho::Aprovado->value)->count())->toBeGreaterThan(0)
        ->and(EquipeTrabalho::query()->where('status', StatusInscricaoEquipeTrabalho::Cancelado->value)->count())->toBeGreaterThan(0);
});

it('can rerun registration seeders without duplicating demo records', function () {
    $this->seed(TriboSeeder::class);
    $this->seed(CampistaSeeder::class);
    $this->seed(EquipeTrabalhoSeeder::class);
    $this->seed(TriboSeeder::class);
    $this->seed(CampistaSeeder::class);
    $this->seed(EquipeTrabalhoSeeder::class);

    expect(Tribo::query()->count())->toBe(10)
        ->and(Campista::query()->count())->toBe(120)
        ->and(EquipeTrabalho::query()->count())->toBe(200);
});

it('includes registration demo data in the main database seeder for local and testing environments', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Tribo::query()->count())->toBe(10)
        ->and(Campista::query()->count())->toBe(120)
        ->and(EquipeTrabalho::query()->count())->toBe(200);
});

function campistaSeederRequiredFormKeys(): array
{
    return [
        'data_nacimento',
        'sexo',
        'altura',
        'peso',
        'rede_social',
        'telefone_campista',
        'telefone_reponsavel_1',
        'telefone_reponsavel_nome_1',
        'telefone_reponsavel_2',
        'telefone_reponsavel_nome_2',
        'cep',
        'rua',
        'numero',
        'ponto_referencia',
        'bairro',
        'cidade',
        'estado',
        'paroquia',
        'comunidade',
        'toma_remedio',
        'remedio',
        'tem_recomendacao',
        'recomendacao',
        'tamanho_camiseta',
        'tamanho_camiseta_outro',
        'ja_participou_retiro',
        'retiro_que_participou',
        'algum_parente',
        'algum_parente_participante',
        'declaro',
        'aceite_termo_inscricao',
        'aceitar_politica_privacidade',
        'comprovante_nome',
        'comprovante',
    ];
}

function equipeTrabalhoSeederRequiredFormKeys(): array
{
    return [
        'data_nacimento',
        'sexo',
        'rede_social',
        'telefone',
        'reponsavel_nome',
        'reponsavel_telefone',
        'cep',
        'rua',
        'numero',
        'ponto_referencia',
        'bairro',
        'cidade',
        'estado',
        'ja_participou_retiro',
        'retiro_que_participou',
        'pode_missas_diarias',
        'tamanho_camiseta',
        'tamanho_camiseta_outro',
        'servir_no_acampamento',
    ];
}
