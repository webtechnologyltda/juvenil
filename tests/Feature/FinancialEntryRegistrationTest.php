<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\Pages\CreateLancamento;
use App\Filament\Resources\LancamentoResource\Pages\EditLancamento;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\FinancialEntryRegistration;
use App\Models\Lancamento;
use App\Models\User;
use App\Support\EnumOptionBadge;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Carbon\Carbon;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('defaults financial entry date to the application current date', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-08 09:30:00', config('app.timezone')));

    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user);

    Livewire::test(CreateLancamento::class)
        ->assertFormSet([
            'data' => '2026-06-08',
        ]);

    Carbon::setTestNow();
});

it('renders financial status and payment select options with enum icons and colors', function () {
    if (! class_exists(EnumOptionBadge::class)) {
        $this->fail('Financial enum select options must be rendered by EnumOptionBadge.');
    }

    expect((string) EnumOptionBadge::option(StatusLacamento::Pago))
        ->toContain('Pago')
        ->toContain('polaris-payment-icon')
        ->toContain('data-enum-color="success"')
        ->and((string) EnumOptionBadge::option(FormaPagamento::Pix))
        ->toContain('Pix')
        ->toContain('fab-pix')
        ->toContain('data-enum-color="teal"');

    expect(file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php')))
        ->toContain('use App\Support\EnumOptionBadge;')
        ->toContain('EnumOptionBadge::options(StatusLacamento::class)')
        ->toContain('EnumOptionBadge::options(FormaPagamento::class)')
        ->toContain('->enum(StatusLacamento::class)')
        ->toContain('->enum(FormaPagamento::class)');
});

it('links one paid financial entry to multiple campista registrations and marks them paid', function () {
    seedFinancialRegistrationSettings(25000);

    $joao = Campista::factory()->create([
        'nome' => 'João Campista',
        'status' => StatusInscricao::Pendente->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'dia_pagamento' => null,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $maria = Campista::factory()->create([
        'nome' => 'Maria Campista',
        'status' => StatusInscricao::Pendente->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'dia_pagamento' => null,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $lancamento = paidFinancialEntry(50000);

    app(RegistrationPaymentAllocator::class)->sync($lancamento, [
        [
            'registration_type' => Campista::class,
            'registration_id' => $joao->id,
            'amount' => 25000,
        ],
        [
            'registration_type' => Campista::class,
            'registration_id' => $maria->id,
            'amount' => 25000,
        ],
    ]);

    expect($lancamento->fresh()->registrationPayments)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(FinancialEntryRegistration::class)
        ->and($joao->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and($joao->fresh()->forma_pagamento)->toBe(FormaPagamento::Pix)
        ->and($joao->fresh()->dia_pagamento?->format('Y-m-d'))->toBe('2026-07-01')
        ->and($maria->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and(app(RegistrationPaymentAllocator::class)->paidAmountFor($joao->fresh()))->toBe(25000)
        ->and(app(RegistrationPaymentAllocator::class)->remainingAmountFor($maria->fresh()))->toBe(0);
});

it('keeps a campista pending until multiple partial financial entries complete the expected amount', function () {
    seedFinancialRegistrationSettings(25000);

    $campista = Campista::factory()->create([
        'nome' => 'Campista Parcial',
        'status' => StatusInscricao::Pendente->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'dia_pagamento' => null,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    app(RegistrationPaymentAllocator::class)->sync(paidFinancialEntry(10000), [
        [
            'registration_type' => Campista::class,
            'registration_id' => $campista->id,
            'amount' => 10000,
        ],
    ]);

    expect($campista->fresh()->status)
        ->toBe(StatusInscricao::Pendente)
        ->and(app(RegistrationPaymentAllocator::class)->paidAmountFor($campista->fresh()))->toBe(10000)
        ->and(app(RegistrationPaymentAllocator::class)->remainingAmountFor($campista->fresh()))->toBe(15000);

    app(RegistrationPaymentAllocator::class)->sync(paidFinancialEntry(15000), [
        [
            'registration_type' => Campista::class,
            'registration_id' => $campista->id,
            'amount' => 15000,
        ],
    ]);

    expect($campista->fresh()->status)
        ->toBe(StatusInscricao::Pago)
        ->and(app(RegistrationPaymentAllocator::class)->paidAmountFor($campista->fresh()))->toBe(25000)
        ->and(app(RegistrationPaymentAllocator::class)->remainingAmountFor($campista->fresh()))->toBe(0);
});

it('supports team work registrations through the same financial entry registration link', function () {
    seedFinancialRegistrationSettings(25000);

    $equipe = EquipeTrabalho::factory()->create([
        'nome' => 'Voluntário Equipe',
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
    ]);
    $lancamento = paidFinancialEntry(25000);

    app(RegistrationPaymentAllocator::class)->sync($lancamento, [
        [
            'registration_type' => EquipeTrabalho::class,
            'registration_id' => $equipe->id,
            'amount' => 25000,
        ],
    ]);

    expect($lancamento->fresh()->registrationPayments)
        ->toHaveCount(1)
        ->and($lancamento->fresh()->registrationPayments->first()->registration)
        ->toBeInstanceOf(EquipeTrabalho::class)
        ->and($equipe->fresh()->status)
        ->toBe(StatusInscricaoEquipeTrabalho::Aprovado);
});

it('searches registration payment options by registration name', function () {
    seedFinancialRegistrationSettings(25000);

    $lucas = Campista::factory()->create([
        'nome' => 'Lucas da Silva',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    Campista::factory()->create([
        'nome' => 'Maria Souza',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    $results = app(RegistrationPaymentAllocator::class)
        ->registrationSearchResults(Campista::class, 'Lucas');

    expect($results)
        ->toHaveKey($lucas->id)
        ->and(implode(' ', $results))->toContain('Lucas da Silva')
        ->and(implode(' ', $results))->not->toContain('Maria Souza');
});

it('creates a financial entry with multiple registration links from the Filament create page', function () {
    $this->seed(ShieldSeeder::class);
    seedFinancialRegistrationSettings(25000);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $joao = Campista::factory()->create([
        'nome' => 'João Tela',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $maria = Campista::factory()->create([
        'nome' => 'Maria Tela',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);

    Livewire::test(CreateLancamento::class)
        ->fillForm([
            'nome' => 'PIX João e Maria',
            'valor' => 50000,
            'tipo' => TipoLacamento::Receita->value,
            'categoria_lancamento_id' => null,
            'data' => '2026-07-01',
            'status' => StatusLacamento::Pago->value,
            'forma_pagamento' => FormaPagamento::Pix->value,
            'comprador' => 'Família',
            'descricao' => 'Pagamento agrupado de inscrições',
            'registration_payments' => [
                [
                    'registration_type' => Campista::class,
                    'registration_id' => $joao->id,
                    'amount' => 25000,
                ],
                [
                    'registration_type' => Campista::class,
                    'registration_id' => $maria->id,
                    'amount' => 25000,
                ],
            ],
            'comprovante' => [
                [
                    'observacao' => 'PIX recebido para João e Maria',
                    'url' => ['comprovantes/pix-joao-maria.pdf'],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $lancamento = Lancamento::query()->where('nome', 'PIX João e Maria')->firstOrFail();

    expect($lancamento->valor)
        ->toBe(50000)
        ->and($lancamento->comprador)->toBeNull()
        ->and(data_get($lancamento->comprovante, '0.data.observacao'))->toBe('PIX recebido para João e Maria')
        ->and(data_get($lancamento->comprovante, '0.data'))->not->toHaveKey('comprovante_nome')
        ->and($lancamento->registrationPayments)->toHaveCount(2)
        ->and($joao->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and($maria->fresh()->status)->toBe(StatusInscricao::Pago);
});

it('updates registration links from the Filament edit page without duplicating the financial entry', function () {
    $this->seed(ShieldSeeder::class);
    seedFinancialRegistrationSettings(25000);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $joao = Campista::factory()->create([
        'nome' => 'João Editado',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $maria = Campista::factory()->create([
        'nome' => 'Maria Editada',
        'status' => StatusInscricao::Pendente->value,
        'tribo_id' => null,
        'user_id' => null,
    ]);
    $lancamento = paidFinancialEntry(50000);

    app(RegistrationPaymentAllocator::class)->sync($lancamento, [
        [
            'registration_type' => Campista::class,
            'registration_id' => $joao->id,
            'amount' => 25000,
        ],
    ]);

    Livewire::test(EditLancamento::class, ['record' => $lancamento->getKey()])
        ->fillForm([
            'nome' => 'PIX editado',
            'valor' => 50000,
            'tipo' => TipoLacamento::Receita->value,
            'categoria_lancamento_id' => null,
            'data' => '2026-07-01',
            'status' => StatusLacamento::Pago->value,
            'forma_pagamento' => FormaPagamento::Pix->value,
            'comprador' => 'Família',
            'descricao' => 'Pagamento editado',
            'registration_payments' => [
                [
                    'registration_type' => Campista::class,
                    'registration_id' => $joao->id,
                    'amount' => 25000,
                ],
                [
                    'registration_type' => Campista::class,
                    'registration_id' => $maria->id,
                    'amount' => 25000,
                ],
            ],
            'comprovante' => [
                [
                    'observacao' => 'Comprovante substituído na edição',
                    'url' => ['comprovantes/pix-editado.pdf'],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Lancamento::query()->count())
        ->toBe(1)
        ->and($lancamento->fresh()->comprador)->toBeNull()
        ->and(data_get($lancamento->fresh()->comprovante, '0.data.observacao'))->toBe('Comprovante substituído na edição')
        ->and(data_get($lancamento->fresh()->comprovante, '0.data'))->not->toHaveKey('comprovante_nome')
        ->and($lancamento->fresh()->registrationPayments)->toHaveCount(2)
        ->and($joao->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and($maria->fresh()->status)->toBe(StatusInscricao::Pago);
});

it('rejects financial entry registration amounts above the entry total or remaining registration balance', function () {
    seedFinancialRegistrationSettings(25000);

    $joao = Campista::factory()->create(['nome' => 'João', 'tribo_id' => null, 'user_id' => null]);
    $maria = Campista::factory()->create(['nome' => 'Maria', 'tribo_id' => null, 'user_id' => null]);

    expect(fn () => app(RegistrationPaymentAllocator::class)->sync(paidFinancialEntry(25000), [
        [
            'registration_type' => Campista::class,
            'registration_id' => $joao->id,
            'amount' => 20000,
        ],
        [
            'registration_type' => Campista::class,
            'registration_id' => $maria->id,
            'amount' => 10000,
        ],
    ]))->toThrow(ValidationException::class);

    app(RegistrationPaymentAllocator::class)->sync(paidFinancialEntry(20000), [
        [
            'registration_type' => Campista::class,
            'registration_id' => $joao->id,
            'amount' => 20000,
        ],
    ]);

    expect(fn () => app(RegistrationPaymentAllocator::class)->sync(paidFinancialEntry(10000), [
        [
            'registration_type' => Campista::class,
            'registration_id' => $joao->id,
            'amount' => 10000,
        ],
    ]))->toThrow(ValidationException::class);
});

it('exposes registration payment links in the financial entry form and table', function () {
    $form = file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php'));
    $resource = file_get_contents(app_path('Filament/Resources/LancamentoResource.php'));
    $createPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/CreateLancamento.php'));
    $editPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/EditLancamento.php'));

    expect($form)
        ->toContain("Repeater::make('registration_payments')")
        ->toContain("Repeater::make('comprovante')")
        ->toContain("Select::make('registration_type')")
        ->toContain("Select::make('registration_id')")
        ->toContain('getSearchResultsUsing')
        ->toContain("Money::make('amount')")
        ->toContain("RichEditor::make('descricao')")
        ->toContain("->toolbarButtons([['bold', 'italic', 'underline'], ['bulletList', 'orderedList'], ['link', 'clearFormatting']])")
        ->not->toContain("Textarea::make('descricao')")
        ->toContain('RegistrationPaymentAllocator::registrationTypeOptions')
        ->toContain('registrationOptions')
        ->toContain('registrationSearchResults')
        ->toContain("Textarea::make('observacao')")
        ->not->toContain("Builder::make('comprovante')")
        ->not->toContain('use Filament\Forms\Components\Builder;')
        ->not->toContain('use Filament\Forms\Components\Builder\Block;')
        ->not->toContain("TextInput::make('comprovante_nome')")
        ->and($resource)
        ->toContain("TextColumn::make('registration_payments_summary')")
        ->toContain("->with(['categoria', 'registrationPayments.registration'])")
        ->and($createPage)
        ->toContain('RegistrationPaymentAllocator::class')
        ->toContain('registrationPaymentData')
        ->and($editPage)
        ->toContain('RegistrationPaymentAllocator::class')
        ->toContain('registrationPaymentsFormState');

    expect(Schema::getColumnType('lancamentos', 'descricao'))->toBe('text');
});

it('normalizes legacy receipt names into optional observations', function () {
    $normalized = LancamentoForm::normalizeComprovanteState([
        [
            'type' => 'anexar_comprovante',
            'data' => [
                'comprovante_nome' => 'PIX antigo',
                'url' => 'comprovantes/pix-antigo.pdf',
            ],
        ],
    ]);

    expect(data_get($normalized, '0.data.observacao'))
        ->toBe('PIX antigo')
        ->and(data_get($normalized, '0.data.url'))->toBe(['comprovantes/pix-antigo.pdf'])
        ->and(data_get($normalized, '0.data'))->not->toHaveKey('comprovante_nome');
});

it('keeps comprador only for expense financial entries', function () {
    expect(LancamentoForm::normalizeCompradorForType([
        'tipo' => TipoLacamento::Receita->value,
        'comprador' => 'Família Receita',
    ]))
        ->toHaveKey('comprador', null)
        ->and(LancamentoForm::normalizeCompradorForType([
            'tipo' => TipoLacamento::Doacao,
            'comprador' => 'Família Doação',
        ]))->toHaveKey('comprador', null)
        ->and(LancamentoForm::normalizeCompradorForType([
            'tipo' => TipoLacamento::Despesa->value,
            'comprador' => 'Comprador Despesa',
        ]))->toHaveKey('comprador', 'Comprador Despesa');
});

function seedFinancialRegistrationSettings(int $amount): void
{
    DB::table('settings')->updateOrInsert(
        [
            'group' => 'general',
            'name' => 'valor_acampamento',
        ],
        [
            'payload' => json_encode($amount),
        ],
    );
}

function paidFinancialEntry(int $amount): Lancamento
{
    return Lancamento::factory()->create([
        'nome' => 'PIX inscrições',
        'descricao' => 'Pagamento de inscrições',
        'comprador' => 'Responsável',
        'data' => '2026-07-01',
        'valor' => $amount,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'comprovante' => [],
        'user_id' => null,
    ]);
}
