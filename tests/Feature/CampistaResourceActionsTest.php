<?php

use App\Actions\Campistas\CancelCampistaRegistrationAction;
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
use Filament\Actions\Testing\TestAction;
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

it('asks what to do with a paid payment while keeping the cancellation reason field', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $campista = Campista::factory()->create([
        'nome' => 'Mariana Modal',
        'status' => StatusInscricao::Pago,
        'observacoes' => 'Observação anterior',
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $lancamento = campistaActionsPaidLaunch($campista);

    Livewire::test(ListCampistas::class)
        ->loadTable()
        ->mountAction(TestAction::make('Cancelar')->table($campista))
        ->assertMountedActionModalSee([
            'Cancelar inscrição paga',
            'Observação',
            'O que deseja fazer com o pagamento?',
            'Cancelar o pagamento e registrar o estorno',
            'Manter o pagamento como pago (sem estorno)',
        ])
        ->assertActionDataSet([
            'observacoes' => 'Observação anterior',
            'payment_action' => null,
        ])
        ->setActionData([
            'observacoes' => 'Irá fazer cirurgia',
        ])
        ->callMountedAction()
        ->assertHasFormErrors(['payment_action' => 'required']);

    expect($campista->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and($lancamento->fresh()->status)->toBe(StatusLacamento::Pago);
});

it('cancels and annotates a paid payment when the amount was refunded', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create(['name' => 'Usuário Financeiro']);
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $campista = Campista::factory()->create([
        'nome' => 'Mariana Estorno',
        'status' => StatusInscricao::Pago,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $lancamento = campistaActionsPaidLaunch($campista, 27550, 'Pagamento confirmado');

    Livewire::test(ListCampistas::class)
        ->loadTable()
        ->callAction(TestAction::make('Cancelar')->table($campista), [
            'observacoes' => 'Irá fazer cirurgia',
            'payment_action' => CancelCampistaRegistrationAction::PAYMENT_REFUND,
        ])
        ->assertHasNoFormErrors();

    expect($campista->fresh())
        ->status->toBe(StatusInscricao::Cancelado)
        ->observacoes->toBe('Irá fazer cirurgia')
        ->and($lancamento->fresh())
        ->status->toBe(StatusLacamento::Cancelado)
        ->descricao->toContain('Pagamento confirmado')
        ->descricao->toContain('Quantia estornada: R$ 275,50')
        ->descricao->toContain('Motivo do cancelamento: Irá fazer cirurgia')
        ->descricao->toContain('Registrado por: Usuário Financeiro');
});

it('keeps a paid payment unchanged when there was no refund', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $campista = Campista::factory()->create([
        'nome' => 'Mariana Sem Estorno',
        'status' => StatusInscricao::Pago,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $lancamento = campistaActionsPaidLaunch($campista, 25000, 'Pagamento mantido');

    Livewire::test(ListCampistas::class)
        ->loadTable()
        ->callAction(TestAction::make('Cancelar')->table($campista), [
            'observacoes' => 'Não solicitou estorno',
            'payment_action' => CancelCampistaRegistrationAction::PAYMENT_KEEP_PAID,
        ])
        ->assertHasNoFormErrors();

    expect($campista->fresh())
        ->status->toBe(StatusInscricao::Cancelado)
        ->observacoes->toBe('Não solicitou estorno')
        ->and($lancamento->fresh())
        ->status->toBe(StatusLacamento::Pago)
        ->descricao->toBe('Pagamento mantido');
});

it('separates only the refunded registration when a paid launch has other items', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $refundedCampista = Campista::factory()->create([
        'nome' => 'Campista Estornado',
        'status' => StatusInscricao::Pago,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $paidCampista = Campista::factory()->create([
        'nome' => 'Campista Mantido',
        'status' => StatusInscricao::Pago,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $category = campistaActionsInscricaoCategory();
    $lancamento = Lancamento::factory()->create([
        'nome' => 'Pagamento compartilhado',
        'descricao' => 'Duas inscrições',
        'valor' => 50000,
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'data' => '2026-07-14',
        'comprovante' => [],
        'user_id' => null,
    ]);

    foreach ([$refundedCampista, $paidCampista] as $campista) {
        $lancamento->items()->create([
            'nome' => $campista->nome,
            'valor' => 25000,
            'categoria_lancamento_id' => $category->id,
            'registration_type' => $campista::class,
            'registration_id' => $campista->id,
        ]);
    }

    app(CancelCampistaRegistrationAction::class)->execute(
        campista: $refundedCampista,
        reason: 'Estorno parcial',
        paymentAction: CancelCampistaRegistrationAction::PAYMENT_REFUND,
    );

    $cancelledLancamento = Lancamento::query()
        ->where('id', '<>', $lancamento->id)
        ->where('status', StatusLacamento::Cancelado)
        ->firstOrFail();

    expect($lancamento->fresh())
        ->status->toBe(StatusLacamento::Pago)
        ->valor->toBe(25000)
        ->and($lancamento->items()->pluck('registration_id')->all())->toBe([$paidCampista->id])
        ->and($cancelledLancamento)
        ->status->toBe(StatusLacamento::Cancelado)
        ->valor->toBe(25000)
        ->descricao->toContain('Quantia estornada: R$ 250,00')
        ->and($cancelledLancamento->items()->pluck('registration_id')->all())->toBe([$refundedCampista->id]);
});

it('keeps the original reason-only modal when the registration has no paid payment', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $campista = Campista::factory()->create([
        'nome' => 'Mariana Pendente',
        'status' => StatusInscricao::Pendente,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    Livewire::test(ListCampistas::class)
        ->loadTable()
        ->mountAction(TestAction::make('Cancelar')->table($campista))
        ->assertMountedActionModalSee(['Cancelar inscrição', 'Observação'])
        ->assertMountedActionModalDontSee('O que deseja fazer com o pagamento?')
        ->setActionData([
            'observacoes' => 'Desistência antes do pagamento',
        ])
        ->callMountedAction()
        ->assertHasNoFormErrors();

    expect($campista->fresh())
        ->status->toBe(StatusInscricao::Cancelado)
        ->observacoes->toBe('Desistência antes do pagamento');
});

function campistaActionsInscricaoCategory(): CategoriaLancamento
{
    CategoriaLancamento::ensureSystemDefaults();

    return CategoriaLancamento::query()
        ->where('system_key', CategoriaLancamento::SYSTEM_CATEGORY_INSCRICAO)
        ->firstOrFail();
}

function campistaActionsPaidLaunch(
    Campista $campista,
    int $amount = 25000,
    ?string $description = 'Pagamento da inscrição',
): Lancamento {
    $lancamento = Lancamento::factory()->create([
        'nome' => 'Pagamento - '.$campista->nome,
        'descricao' => $description,
        'valor' => $amount,
        'tipo' => TipoLacamento::Receita,
        'status' => StatusLacamento::Pago,
        'forma_pagamento' => FormaPagamento::Pix,
        'data' => '2026-07-14',
        'comprovante' => [],
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => $campista->nome,
        'valor' => $amount,
        'categoria_lancamento_id' => campistaActionsInscricaoCategory()->id,
        'registration_type' => $campista::class,
        'registration_id' => $campista->id,
    ]);

    return $lancamento;
}
