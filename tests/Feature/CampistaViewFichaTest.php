<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the campista view as a registration ficha with a single styled header edit action', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $campista = Campista::factory()->create([
        'nome' => 'Lucas Teste Juvenil',
        'avatar_url' => 'foto-formulario/lucas.png',
        'status' => StatusInscricao::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'presenca' => true,
        'tribo_id' => null,
        'user_id' => null,
        'form_data' => [
            'data_nacimento' => '15/02/2000',
            'sexo' => 'M',
            'altura' => '170',
            'peso' => '70',
            'telefone_campista' => '(47) 9 9999-9999',
            'telefone_reponsavel_nome_1' => 'Maria Responsavel',
            'telefone_reponsavel_1' => '(47) 9 8888-8888',
            'rua' => 'Rua do Acampamento',
            'numero' => '22',
            'ponto_referencia' => 'Casa 2',
            'bairro' => 'Centro',
            'cidade' => 'Navegantes',
            'estado' => 'SC',
            'paroquia' => 0,
            'comunidade' => 1,
            'toma_remedio' => true,
            'remedio' => 'Dipirona a cada 8 horas',
            'tem_recomendacao' => true,
            'recomendacao' => 'Evitar amendoim',
            'tamanho_camiseta' => 'M',
            'declaro' => true,
        ],
    ]);

    $editUrl = route('filament.admin.resources.campistas.edit', ['record' => $campista]);
    $viewPage = file_get_contents(app_path('Filament/Resources/CampistaResource/Pages/ViewCampista.php'));
    $viewBlade = file_get_contents(resource_path('views/filament/resources/campista-resource/pages/view-campista.blade.php'));
    $adminCss = file_get_contents(resource_path('css/filament/admin/theme.css'));

    $this->actingAs($user)
        ->get(route('filament.admin.resources.campistas.view', ['record' => $campista]))
        ->assertOk()
        ->assertSee('juvenil-registration-card', false)
        ->assertSee('juvenil-registration-header-edit', false)
        ->assertSee('Ficha de inscrição')
        ->assertSee('Ficha oficial')
        ->assertSee('Lucas Teste Juvenil')
        ->assertSee('Dados pessoais')
        ->assertSee('Contato e responsável')
        ->assertSee('Responsável')
        ->assertSee('Maria Responsavel')
        ->assertSee('(47) 9 8888-8888')
        ->assertDontSee('Contato 2')
        ->assertDontSee('Telefone 2')
        ->assertSee('Endereço')
        ->assertSee('Comunidade e experiência')
        ->assertSee('Saúde e cuidados')
        ->assertSee('Controle da inscrição')
        ->assertSee('Complemento')
        ->assertSee('Casa 2')
        ->assertDontSee('Ponto de referência')
        ->assertSee('Dipirona a cada 8 horas')
        ->assertSee('Editar inscrição')
        ->assertSee($editUrl, false)
        ->assertDontSee('juvenil-registration-card__edit', false)
        ->assertDontSee('Clique aqui')
        ->assertDontSee('Para adicionar uma foto sua');

    expect($viewPage)
        ->toContain("->extraAttributes(['class' => 'juvenil-registration-header-edit'], merge: true)")
        ->not->toContain("'editUrl'")
        ->and($viewBlade)
        ->not->toContain('juvenil-registration-card__edit')
        ->not->toContain('$editUrl')
        ->and($adminCss)
        ->toContain('.juvenil-registration-header-edit')
        ->toContain('min-height: 3rem;')
        ->toContain('border: 1px solid rgba(244, 107, 18, 0.72);')
        ->toContain('text-transform: uppercase;')
        ->not->toContain('.juvenil-registration-card__edit');
});
