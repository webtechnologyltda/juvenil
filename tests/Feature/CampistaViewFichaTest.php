<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\Tribo;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the campista view as a registration ficha with a single styled header edit action', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $tribo = Tribo::query()->firstOrCreate(['cor' => 'Preta']);

    $campista = Campista::factory()->create([
        'nome' => 'Lucas Teste Juvenil',
        'avatar_url' => 'foto-formulario/lucas.png',
        'status' => StatusInscricao::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'presenca' => true,
        'tribo_id' => $tribo->id,
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
        ->assertSee('data-summary-icon="polaris-payment-icon"', false)
        ->assertSee('data-summary-color="success"', false)
        ->assertSee('data-summary-icon="fab-pix"', false)
        ->assertSee('data-summary-color="teal"', false)
        ->assertSee('data-summary-icon="heroicon-o-check-circle"', false)
        ->assertSee('data-summary-icon="heroicon-o-flag"', false)
        ->assertSee('data-summary-color="tribe"', false)
        ->assertSee('--summary-accent: #111827', false)
        ->assertSee('juvenil-registration-card__summary-badge-icon', false)
        ->assertDontSee('juvenil-registration-card__summary-color', false)
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
        ->assertDontSee('Comprovantes anexados')
        ->assertDontSee('juvenil-registration-card__documents', false)
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
        ->not->toContain("['label' => 'Comprovante'")
        ->and($viewBlade)
        ->toContain('juvenil-registration-card__summary-badge-icon')
        ->not->toContain('juvenil-registration-card__summary-color')
        ->not->toContain('juvenil-registration-card__summary-icon')
        ->not->toContain('juvenil-registration-card__section--documents')
        ->not->toContain('juvenil-registration-card__documents')
        ->not->toContain('juvenil-registration-card__edit')
        ->not->toContain('$editUrl')
        ->and($adminCss)
        ->toContain('.juvenil-registration-header-edit')
        ->toContain('min-height: 3rem;')
        ->toContain('border: 1px solid rgba(244, 107, 18, 0.72);')
        ->toContain('text-transform: uppercase;')
        ->toContain('.juvenil-registration-card__summary-item--tribe .juvenil-registration-card__summary-badge-icon')
        ->toContain('color: var(--summary-accent);')
        ->not->toContain('.juvenil-registration-card__summary-color')
        ->not->toContain('.juvenil-registration-card__section--documents')
        ->not->toContain('.juvenil-registration-card__documents')
        ->not->toContain('.juvenil-registration-card__edit');
});

it('shows linked financial payments on the campista view when the user can view all financial entries', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $campista = Campista::factory()->create([
        'nome' => 'Ana Pagamento Vinculado',
        'status' => StatusInscricao::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    $lancamento = campistaViewFichaLinkedPayment($campista, [
        'nome' => 'PIX Ana Pagamento Vinculado',
        'valor' => 37500,
        'data' => '2026-07-04',
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
    ]);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.campistas.view', ['record' => $campista]))
        ->assertOk()
        ->assertSee('Pagamentos vinculados')
        ->assertSee('PIX Ana Pagamento Vinculado')
        ->assertSee('R$ 375,00')
        ->assertSee('04/07/2026')
        ->assertSee('Pix')
        ->assertSee('data-payment-icon="fab-pix"', false)
        ->assertSee('data-payment-color="teal"', false)
        ->assertSee('Pago')
        ->assertSee('data-payment-icon="polaris-payment-icon"', false)
        ->assertSee('data-payment-color="success"', false)
        ->assertSee('Visualizar lançamento')
        ->assertSee(route('filament.admin.resources.lancamentos.view', ['record' => $lancamento]), false);
});

it('hides linked financial payments on the campista view without financial entry view permissions', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Administrador');

    $campista = Campista::factory()->create([
        'nome' => 'Beatriz Pagamento Restrito',
        'status' => StatusInscricao::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    campistaViewFichaLinkedPayment($campista, [
        'nome' => 'PIX Beatriz Restrito',
        'valor' => 41000,
        'data' => '2026-07-05',
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
    ]);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.campistas.view', ['record' => $campista]))
        ->assertOk()
        ->assertDontSee('Pagamentos vinculados')
        ->assertDontSee('PIX Beatriz Restrito')
        ->assertDontSee('R$ 410,00')
        ->assertDontSee('Visualizar lançamento');
});

function campistaViewFichaLinkedPayment(Campista $campista, array $overrides = []): Lancamento
{
    CategoriaLancamento::ensureSystemDefaults();

    $category = CategoriaLancamento::query()
        ->where('system_key', CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO)
        ->firstOrFail();

    $lancamento = Lancamento::factory()->create(array_merge([
        'nome' => 'PIX '.$campista->nome,
        'descricao' => 'Pagamento vinculado na ficha',
        'valor' => 25000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'data' => '2026-07-01',
        'comprovante' => [],
        'user_id' => null,
    ], $overrides));

    $lancamento->items()->create([
        'nome' => $campista->nome,
        'valor' => abs((int) $lancamento->valor),
        'categoria_lancamento_id' => $category->id,
        'registration_type' => $campista::class,
        'registration_id' => $campista->id,
    ]);

    return $lancamento;
}
