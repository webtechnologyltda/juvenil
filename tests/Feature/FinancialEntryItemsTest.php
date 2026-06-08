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
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
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

it('links one paid financial entry to multiple registration items and marks them paid', function () {
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $category = financialItemRegistrationCategory(Campista::class);
    $joao = financialItemCampista('João Campista');
    $maria = financialItemCampista('Maria Campista');
    $lancamento = financialItemEntry(['status' => StatusLacamento::Pago->value]);

    app(RegistrationPaymentAllocator::class)->syncItems($lancamento, [
        financialItemPayload($category, $joao),
        financialItemPayload($category, $maria),
    ]);

    expect($lancamento->fresh()->items)
        ->toHaveCount(2)
        ->each->toBeInstanceOf(LancamentoItem::class)
        ->and($joao->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and($joao->fresh()->forma_pagamento)->toBe(FormaPagamento::Pix)
        ->and($joao->fresh()->dia_pagamento?->format('Y-m-d'))->toBe('2026-07-01')
        ->and($maria->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and(app(RegistrationPaymentAllocator::class)->paidAmountFor($joao->fresh()))->toBe(25000)
        ->and(app(RegistrationPaymentAllocator::class)->remainingAmountFor($maria->fresh()))->toBe(0);
});

it('supports team work registrations through the same financial item link', function () {
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $category = financialItemRegistrationCategory(EquipeTrabalho::class);
    $equipe = EquipeTrabalho::factory()->create([
        'nome' => 'Voluntário Equipe',
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
    ]);
    $lancamento = financialItemEntry(['status' => StatusLacamento::Pago->value]);

    app(RegistrationPaymentAllocator::class)->syncItems($lancamento, [
        financialItemPayload($category, $equipe),
    ]);

    expect($lancamento->fresh()->items)
        ->toHaveCount(1)
        ->and($lancamento->fresh()->items->first()->registration)
        ->toBeInstanceOf(EquipeTrabalho::class)
        ->and($equipe->fresh()->status)
        ->toBe(StatusInscricaoEquipeTrabalho::Aprovado);
});

it('searches registration item options by registration name and hides already linked registrations', function () {
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $category = financialItemRegistrationCategory(Campista::class);
    $lucas = financialItemCampista('Lucas da Silva');
    $maria = financialItemCampista('Maria Souza');

    app(RegistrationPaymentAllocator::class)->syncItems(financialItemEntry(), [
        financialItemPayload($category, $maria),
    ]);

    $results = app(RegistrationPaymentAllocator::class)
        ->registrationSearchResults(Campista::class, 'a');

    expect($results)
        ->toHaveKey($lucas->id)
        ->and(implode(' ', $results))->toContain('Lucas da Silva')
        ->and($results)->not->toHaveKey($maria->id);
});

it('creates a financial entry with multiple items from the Filament create page', function () {
    $this->seed(ShieldSeeder::class);
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $category = financialItemRegistrationCategory(Campista::class);
    $joao = financialItemCampista('João Tela');
    $maria = financialItemCampista('Maria Tela');

    Livewire::test(CreateLancamento::class)
        ->fillForm([
            'nome' => 'PIX João e Maria',
            'tipo' => TipoLacamento::Receita->value,
            'data' => '2026-07-01',
            'status' => StatusLacamento::Pago->value,
            'forma_pagamento' => FormaPagamento::Pix->value,
            'comprador' => 'Família',
            'descricao' => 'Pagamento agrupado de inscrições',
            'items' => [
                financialItemPayload($category, $joao),
                financialItemPayload($category, $maria),
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
        ->and($lancamento->items)->toHaveCount(2)
        ->and($joao->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and($maria->fresh()->status)->toBe(StatusInscricao::Pago);
});

it('allows cash revenue financial entries without receipt attachments', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $payload = financialItemFormPayload([
        'nome' => 'Receita em dinheiro sem comprovante',
        'tipo' => TipoLacamento::Receita->value,
        'forma_pagamento' => FormaPagamento::Dinheiro->value,
    ]);
    unset($payload['comprovante']);

    Livewire::test(CreateLancamento::class)
        ->fillForm($payload)
        ->call('create')
        ->assertHasNoFormErrors();

    $lancamento = Lancamento::query()->where('nome', 'Receita em dinheiro sem comprovante')->firstOrFail();

    expect($lancamento->comprovante)->toBe([])
        ->and($lancamento->items)->toHaveCount(1);
});

it('requires receipt attachments outside cash revenue financial entries', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    foreach ([
        'pix revenue' => [
            'nome' => 'Receita pix sem comprovante',
            'tipo' => TipoLacamento::Receita->value,
            'forma_pagamento' => FormaPagamento::Pix->value,
            'comprador' => null,
        ],
        'cash expense' => [
            'nome' => 'Despesa dinheiro sem comprovante',
            'tipo' => TipoLacamento::Despesa->value,
            'forma_pagamento' => FormaPagamento::Dinheiro->value,
            'comprador' => 'Mercado',
        ],
    ] as $payload) {
        Livewire::test(CreateLancamento::class)
            ->fillForm(financialItemFormPayload([
                ...$payload,
                'comprovante' => [],
            ]))
            ->call('create')
            ->assertHasFormErrors(['comprovante' => 'required']);
    }
});

it('updates financial items from the Filament edit page without duplicating the financial entry', function () {
    $this->seed(ShieldSeeder::class);
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $category = financialItemRegistrationCategory(Campista::class);
    $joao = financialItemCampista('João Editado');
    $maria = financialItemCampista('Maria Editada');
    $lancamento = financialItemEntry(['status' => StatusLacamento::Pago->value]);

    app(RegistrationPaymentAllocator::class)->syncItems($lancamento, [
        financialItemPayload($category, $joao),
    ]);

    Livewire::test(EditLancamento::class, ['record' => $lancamento->getKey()])
        ->fillForm([
            'nome' => 'PIX editado',
            'tipo' => TipoLacamento::Receita->value,
            'data' => '2026-07-01',
            'status' => StatusLacamento::Pago->value,
            'forma_pagamento' => FormaPagamento::Pix->value,
            'comprador' => 'Família',
            'descricao' => 'Pagamento editado',
            'items' => [
                financialItemPayload($category, $joao),
                financialItemPayload($category, $maria),
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
        ->and($lancamento->fresh()->valor)->toBe(50000)
        ->and($lancamento->fresh()->comprador)->toBeNull()
        ->and(data_get($lancamento->fresh()->comprovante, '0.data.observacao'))->toBe('Comprovante substituído na edição')
        ->and($lancamento->fresh()->items)->toHaveCount(2)
        ->and($joao->fresh()->status)->toBe(StatusInscricao::Pago)
        ->and($maria->fresh()->status)->toBe(StatusInscricao::Pago);
});

it('rejects item values above the remaining registration balance and duplicate linked registrations', function () {
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $category = financialItemRegistrationCategory(Campista::class);
    $joao = financialItemCampista('João');

    expect(fn () => app(RegistrationPaymentAllocator::class)->syncItems(financialItemEntry(), [
        financialItemPayload($category, $joao, ['valor' => 30000]),
    ]))->toThrow(ValidationException::class);

    app(RegistrationPaymentAllocator::class)->syncItems(financialItemEntry(), [
        financialItemPayload($category, $joao, ['valor' => 10000]),
    ]);

    expect(fn () => app(RegistrationPaymentAllocator::class)->syncItems(financialItemEntry(), [
        financialItemPayload($category, $joao, ['valor' => 10000]),
    ]))->toThrow(ValidationException::class);
});

it('exposes item links in the financial entry form and table', function () {
    $form = file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php'));
    $resource = file_get_contents(app_path('Filament/Resources/LancamentoResource.php'));
    $createPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/CreateLancamento.php'));
    $editPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/EditLancamento.php'));

    expect($form)
        ->toContain("Repeater::make('items')")
        ->toContain("Repeater::make('comprovante')")
        ->toContain("Select::make('categoria_lancamento_id')")
        ->toContain("Select::make('registration_type')")
        ->toContain("Select::make('registration_id')")
        ->toContain('getSearchResultsUsing')
        ->toContain("Money::make('valor')")
        ->toContain("RichEditor::make('descricao')")
        ->not->toContain("Repeater::make('registration_payments')")
        ->not->toContain("Money::make('amount')")
        ->not->toContain("Textarea::make('descricao')\n                                ->toolbarButtons")
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
        ->toContain("->with(['items.categoria', 'items.registration'])")
        ->toContain("TextColumn::make('batch_code')")
        ->and($createPage)
        ->toContain('RegistrationPaymentAllocator::class')
        ->toContain('itemData')
        ->and($editPage)
        ->toContain('RegistrationPaymentAllocator::class')
        ->toContain('itemsFormState');

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

function seedFinancialItemSettings(int $amount): void
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

function financialItemCampista(string $name): Campista
{
    return Campista::factory()->create([
        'nome' => $name,
        'status' => StatusInscricao::Pendente->value,
        'forma_pagamento' => FormaPagamento::NaoPago->value,
        'dia_pagamento' => null,
        'tribo_id' => null,
        'user_id' => null,
    ]);
}

function financialItemRegistrationCategory(string $registrationType): CategoriaLancamento
{
    return app(RegistrationPaymentAllocator::class)->categoryForRegistrationType($registrationType);
}

function financialItemPayload(CategoriaLancamento $category, Campista|EquipeTrabalho $registration, array $overrides = []): array
{
    return array_replace([
        'nome' => $registration->nome,
        'valor' => 25000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => $registration::class,
        'registration_id' => $registration->id,
        'descricao' => null,
    ], $overrides);
}

function financialItemFormPayload(array $overrides = []): array
{
    CategoriaLancamento::ensureSystemDefaults();

    $type = $overrides['tipo'] ?? TipoLacamento::Receita->value;
    $category = CategoriaLancamento::query()
        ->where('tipo', $type instanceof TipoLacamento ? $type->value : (int) $type)
        ->orderBy('id')
        ->first()
        ?? CategoriaLancamento::query()->create([
            'nome' => 'Categoria financeira',
            'tipo' => $type instanceof TipoLacamento ? $type : TipoLacamento::from((int) $type),
            'cor' => '#f46b12',
            'icone' => 'heroicon-o-banknotes',
            'ativo' => true,
        ]);

    return array_replace([
        'nome' => 'Lançamento financeiro',
        'tipo' => TipoLacamento::Receita->value,
        'data' => '2026-07-01',
        'status' => StatusLacamento::Pago->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'comprador' => null,
        'descricao' => null,
        'items' => [
            [
                'nome' => 'Item financeiro',
                'valor' => 12500,
                'categoria_lancamento_id' => $category->id,
                'registration_type' => null,
                'registration_id' => null,
                'descricao' => null,
            ],
        ],
        'comprovante' => [],
    ], $overrides);
}

function financialItemEntry(array $overrides = []): Lancamento
{
    return Lancamento::factory()->create(array_replace([
        'nome' => 'PIX inscrições',
        'descricao' => 'Pagamento de inscrições',
        'comprador' => 'Responsável',
        'data' => '2026-07-01',
        'valor' => 0,
        'tipo' => TipoLacamento::Receita->value,
        'status' => StatusLacamento::Pendente->value,
        'forma_pagamento' => FormaPagamento::Pix->value,
        'comprovante' => [],
        'user_id' => null,
    ], $overrides));
}
