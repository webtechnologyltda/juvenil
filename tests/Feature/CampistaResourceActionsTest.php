<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\CampistaResource\CampistaForm;
use App\Filament\Resources\CampistaResource\Pages\EditCampista;
use App\Filament\Resources\CampistaResource\Pages\ListCampistas;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('does not expose a manual mark as paid action on campista registrations', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user);

    Livewire::test(ListCampistas::class)
        ->assertTableActionDoesNotExist('Pago');
});

it('removes direct payment and proof fields from the campista form', function () {
    $form = file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php'));

    expect($form)
        ->toContain('Pagamentos vinculados')
        ->toContain('paymentSummaryHtml')
        ->not->toContain("Select::make('forma_pagamento')")
        ->not->toContain("DatePicker::make('dia_pagamento')")
        ->not->toContain("FileUpload::make('form_data.comprovante')")
        ->not->toContain("TextInput::make('form_data.comprovante_nome')")
        ->not->toContain("->afterStateUpdated(fn(Set \$set) => \$set('dia_pagamento'");
});

it('renders campista payment information only from linked financial entries', function () {
    $campista = Campista::factory()->create([
        'nome' => 'João Financeiro',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    $lancamento = Lancamento::factory()->create([
        'nome' => 'PIX João Financeiro',
        'descricao' => 'Pagamento pela tela financeira',
        'valor' => 25000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'data' => '2026-07-01',
        'comprovante' => [],
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => $campista->nome,
        'valor' => 25000,
        'categoria_lancamento_id' => campistaActionsInscricaoCategory()->id,
        'registration_type' => $campista::class,
        'registration_id' => $campista->id,
    ]);

    expect((string) CampistaForm::paymentSummaryHtml($campista))
        ->toContain('PIX João Financeiro')
        ->toContain('R$ 250,00')
        ->toContain('01/07/2026')
        ->toContain('Pix')
        ->toContain('Pago')
        ->not->toContain('form_data.comprovante')
        ->not->toContain('Nome Comprovante');
});

it('renders the linked payment summary on the campista edit form', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $campista = Campista::factory()->create([
        'nome' => 'Maria Financeiro',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    $lancamento = Lancamento::factory()->create([
        'nome' => 'PIX Maria Financeiro',
        'valor' => 25000,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'data' => '2026-07-02',
        'comprovante' => [],
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => $campista->nome,
        'valor' => 25000,
        'categoria_lancamento_id' => campistaActionsInscricaoCategory()->id,
        'registration_type' => $campista::class,
        'registration_id' => $campista->id,
    ]);

    Livewire::test(EditCampista::class, ['record' => $campista->getKey()])
        ->assertSee('Pagamentos vinculados')
        ->assertSee('PIX Maria Financeiro')
        ->assertSee('R$ 250,00')
        ->assertDontSee('Nome Comprovante')
        ->assertDontSee('Data de Pagamento');
});

function campistaActionsInscricaoCategory(): CategoriaLancamento
{
    CategoriaLancamento::ensureSystemDefaults();

    return CategoriaLancamento::query()
        ->where('system_key', CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO)
        ->firstOrFail();
}
