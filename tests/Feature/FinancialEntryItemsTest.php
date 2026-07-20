<?php

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\StatusLacamento;
use App\Enums\TipoEquipeTrabalho;
use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\Pages\CreateLancamento;
use App\Filament\Resources\LancamentoResource\Pages\EditLancamento;
use App\Filament\Resources\LancamentoResource\Pages\ListLancamentos;
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
use Filament\Forms\Components\Repeater;
use Filament\Support\Colors\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

it('renders financial enum select options with icons and colors', function () {
    expect((string) EnumOptionBadge::option(StatusLacamento::Pago))
        ->toContain('Pago')
        ->toContain('polaris-payment-icon')
        ->toContain('data-enum-color="success"')
        ->and((string) EnumOptionBadge::option(FormaPagamento::Pix))
        ->toContain('Pix')
        ->toContain('fab-pix')
        ->toContain('data-enum-color="teal"')
        ->and((string) EnumOptionBadge::option(TipoLacamento::Receita))
        ->toContain('Receita')
        ->toContain('heroicon-m-arrow-trending-up')
        ->toContain('data-enum-color="success"');

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
    seedFinancialItemSettings(25000, teamInternalAmount: 12000);
    CategoriaLancamento::ensureSystemDefaults();

    $category = financialItemRegistrationCategory(EquipeTrabalho::class);
    $equipe = EquipeTrabalho::factory()->create([
        'nome' => 'Voluntário Equipe',
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
        'tipo_equipe' => TipoEquipeTrabalho::Interna->value,
    ]);
    $lancamento = financialItemEntry(['status' => StatusLacamento::Pago->value]);

    app(RegistrationPaymentAllocator::class)->syncItems($lancamento, [
        financialItemPayload($category, $equipe, ['valor' => 12000]),
    ]);

    expect($lancamento->fresh()->items)
        ->toHaveCount(1)
        ->and($lancamento->fresh()->items->first()->registration)
        ->toBeInstanceOf(EquipeTrabalho::class)
        ->and($equipe->fresh()->status)
        ->toBe(StatusInscricaoEquipeTrabalho::Aprovado);
});

it('uses separate expected amounts for internal and external team work registrations', function () {
    seedFinancialItemSettings(35000, teamInternalAmount: 12000, teamExternalAmount: 8000);
    CategoriaLancamento::ensureSystemDefaults();

    $internal = EquipeTrabalho::factory()->create([
        'nome' => 'Equipe Interna Valores',
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
        'tipo_equipe' => TipoEquipeTrabalho::Interna->value,
    ]);
    $external = EquipeTrabalho::factory()->create([
        'nome' => 'Equipe Externa Valores',
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
        'tipo_equipe' => TipoEquipeTrabalho::Externa->value,
    ]);

    $allocator = app(RegistrationPaymentAllocator::class);
    $options = $allocator->registrationOptions(EquipeTrabalho::class);

    expect($allocator->expectedAmountFor($internal))->toBe(12000)
        ->and($allocator->expectedAmountFor($external))->toBe(8000)
        ->and($options[$internal->id])->toContain('valor R$ 120,00')
        ->and($options[$external->id])->toContain('valor R$ 80,00');
});

it('requires a configured team work amount before linking a team registration to a launch', function () {
    seedFinancialItemSettings(35000, teamInternalAmount: 12000, teamExternalAmount: 0);
    CategoriaLancamento::ensureSystemDefaults();

    $category = financialItemRegistrationCategory(EquipeTrabalho::class);
    $external = EquipeTrabalho::factory()->create([
        'nome' => 'Equipe Externa Sem Valor',
        'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
        'tipo_equipe' => TipoEquipeTrabalho::Externa->value,
    ]);

    try {
        app(RegistrationPaymentAllocator::class)->syncItems(financialItemEntry(), [
            financialItemPayload($category, $external, ['valor' => 8000]),
        ]);
    } catch (ValidationException $exception) {
        expect($exception->errors()['items'][0])
            ->toContain('Configure um valor maior que zero para equipe de trabalho externa');

        return;
    }

    $this->fail('A equipe externa sem valor configurado deveria falhar na validação.');
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

it('allows linking the same registration to different item categories without consuming registration balance twice', function () {
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $registrationCategory = financialItemRegistrationCategory(Campista::class);
    $extraCategory = CategoriaLancamento::query()->create([
        'nome' => 'Camiseta do acampamento',
        'tipo' => TipoLacamento::Receita,
        'cor' => '#14b8a6',
        'icone' => 'heroicon-o-shopping-bag',
        'ativo' => true,
    ]);
    $campista = financialItemCampista('Camila Multicategoria');
    $lancamento = financialItemEntry(['status' => StatusLacamento::Pago->value]);

    app(RegistrationPaymentAllocator::class)->syncItems($lancamento, [
        financialItemPayload($registrationCategory, $campista),
        financialItemPayload($extraCategory, $campista, [
            'nome' => 'Camiseta Camila Multicategoria',
            'valor' => 5000,
        ]),
    ]);

    expect($lancamento->fresh()->items)
        ->toHaveCount(2)
        ->and($lancamento->fresh()->valor)->toBe(30000)
        ->and(app(RegistrationPaymentAllocator::class)->paidAmountFor($campista->fresh()))->toBe(25000)
        ->and(app(RegistrationPaymentAllocator::class)->remainingAmountFor($campista->fresh()))->toBe(0)
        ->and($campista->fresh()->status)->toBe(StatusInscricao::Pago);
});

it('keeps paid registrations selectable for non registration item categories', function () {
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $registrationCategory = financialItemRegistrationCategory(Campista::class);
    $extraCategory = CategoriaLancamento::query()->create([
        'nome' => 'Transporte do campista',
        'tipo' => TipoLacamento::Receita,
        'cor' => '#0ea5e9',
        'icone' => 'heroicon-o-truck',
        'ativo' => true,
    ]);
    $campista = financialItemCampista('Bruna Categoria Extra');

    app(RegistrationPaymentAllocator::class)->syncItems(financialItemEntry(), [
        financialItemPayload($registrationCategory, $campista),
    ]);

    $allocator = app(RegistrationPaymentAllocator::class);

    expect($allocator->registrationOptions(Campista::class))
        ->not->toHaveKey($campista->id)
        ->and($allocator->registrationOptions(Campista::class, categoryId: $extraCategory->id))
        ->toHaveKey($campista->id);
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

it('allows pending financial entries without receipt attachments', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    Livewire::test(CreateLancamento::class)
        ->fillForm(financialItemFormPayload([
            'nome' => 'PIX pendente sem comprovante',
            'status' => StatusLacamento::Pendente->value,
            'tipo' => TipoLacamento::Receita->value,
            'forma_pagamento' => FormaPagamento::Pix->value,
            'comprovante' => [],
        ]))
        ->call('create')
        ->assertHasNoFormErrors();

    $lancamento = Lancamento::query()->where('nome', 'PIX pendente sem comprovante')->firstOrFail();

    expect($lancamento->comprovante)
        ->toBe([])
        ->and($lancamento->status)->toBe(StatusLacamento::Pendente);
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

it('notifies the user when duplicate registration category links are submitted from the form', function () {
    $this->seed(ShieldSeeder::class);
    seedFinancialItemSettings(25000);
    CategoriaLancamento::ensureSystemDefaults();

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $category = financialItemRegistrationCategory(Campista::class);
    $campista = financialItemCampista('Vitória Costa 119');

    Livewire::test(CreateLancamento::class)
        ->fillForm(financialItemFormPayload([
            'nome' => 'Pagamento duplicado Vitória',
            'items' => [
                financialItemPayload($category, $campista, ['valor' => 3000]),
                financialItemPayload($category, $campista, ['valor' => 6000]),
            ],
            'comprovante' => [
                [
                    'observacao' => 'Comprovante duplicado',
                    'url' => ['comprovantes/pix-vitoria.pdf'],
                ],
            ],
        ]))
        ->call('create')
        ->assertNotified('Não foi possível salvar o lançamento');

    expect(Lancamento::query()->where('nome', 'Pagamento duplicado Vitória')->exists())->toBeFalse();
});

it('exposes item links in the financial entry form and table', function () {
    $form = file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php'));
    $resource = file_get_contents(app_path('Filament/Resources/LancamentoResource.php'));
    $createPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/CreateLancamento.php'));
    $editPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/EditLancamento.php'));
    $campistasItemTable = file_get_contents(app_path('Filament/Resources/LancamentoResource/Tables/LancamentoItemCampistasTable.php'));
    $equipeTrabalhoItemTable = file_get_contents(app_path('Filament/Resources/LancamentoResource/Tables/LancamentoItemEquipeTrabalhoTable.php'));

    expect($form)
        ->toContain("Repeater::make('items')")
        ->toContain("Repeater::make('comprovante')")
        ->toContain("Select::make('categoria_lancamento_id')")
        ->toContain("Select::make('registration_type')")
        ->toContain("ModalTableSelect::make('registration_id')")
        ->toContain('string|HtmlString|null => self::registrationOptionLabel')
        ->toContain('LancamentoItemCampistasTable::class')
        ->toContain('LancamentoItemEquipeTrabalhoTable::class')
        ->toContain('tableArguments')
        ->toContain("->extraAttributes(['class' => 'juvenil-launch-registration-select'], merge: true)")
        ->toContain('->modalWidth(Width::SevenExtraLarge)')
        ->toContain('->slideOver()')
        ->not->toContain('->slideOver(false)')
        ->not->toContain("\n                                    Select::make('registration_id')")
        ->toContain('self::categorySearchResults')
        ->toContain('self::categoryOptionLabel')
        ->toContain("->options(fn (Get \$get): array => self::categoryOptions(\$get('../../tipo')))")
        ->toContain('->preload()')
        ->toContain("TextInput::make('valor')")
        ->toContain("RichEditor::make('descricao')")
        ->not->toContain("Repeater::make('registration_payments')")
        ->not->toContain("Money::make('valor')")
        ->not->toContain("Money::make('amount')")
        ->not->toContain("Textarea::make('descricao')\n                                ->toolbarButtons")
        ->toContain('RegistrationPaymentAllocator::registrationTypeOptions')
        ->toContain('registrationOptions')
        ->toContain('LancamentoRegistrationCard::forCampista')
        ->toContain('LancamentoRegistrationCard::forTeam')
        ->not->toContain('registrationSearchResults')
        ->toContain("Textarea::make('observacao')")
        ->toContain('self::itemsTotalHtml')
        ->toContain("TextInput::make('valor')")
        ->toContain("->mask(RawJs::make(<<<'JS'")
        ->toContain("const digits = \$input.replace(/\\D/g, '')")
        ->toContain('MoneyAmount::toCents')
        ->toContain("->visible(fn (Get \$get): bool => ! self::isExpenseType(\$get('../../tipo')))")
        ->toContain("->dehydrated(fn (Get \$get): bool => ! self::isExpenseType(\$get('../../tipo')))")
        ->toContain('$set("items.{$itemKey}.categoria_lancamento_id", null)')
        ->not->toContain('$set(\'items\', self::itemsWithoutCategories($items))')
        ->not->toContain("\$set('items', [])")
        ->not->toContain("Builder::make('comprovante')")
        ->not->toContain('use Filament\Forms\Components\Builder;')
        ->not->toContain('use Filament\Forms\Components\Builder\Block;')
        ->not->toContain("TextInput::make('comprovante_nome')")
        ->and($resource)
        ->toContain("TextColumn::make('registration_payments_summary')")
        ->toContain('self::withCompactItemRelations($query)')
        ->toContain("'categoria:id,nome,cor,icone'")
        ->toContain("'registration' => fn (MorphTo \$morphTo): MorphTo")
        ->not->toContain("->with(['items.categoria', 'items.registration'])")
        ->toContain("TextColumn::make('batch_code')")
        ->toContain("SelectFilter::make('tipo')")
        ->toContain('EnumOptionBadge::options(TipoLacamento::class)')
        ->toContain("Tipo: '.(TipoLacamento::tryFrom((int) \$state['value'])?->getLabel() ?? \$state['value'])")
        ->toContain("Filter::make('registration_link')")
        ->toContain("->label('Cadastro vinculado')")
        ->toContain("->label('Vínculo com cadastro')")
        ->toContain("'linked' => 'Com cadastro vinculado'")
        ->toContain("'unlinked' => 'Sem cadastro vinculado'")
        ->toContain("->label('Tipo de cadastro')")
        ->toContain("Select::make('registration_type')")
        ->toContain("Filter::make('name_search')")
        ->toContain('whereHasMorph')
        ->toContain("Filter::make('created_at')")
        ->toContain("DatePicker::make('created_from')")
        ->toContain("DatePicker::make('created_until')")
        ->and($createPage)
        ->toContain('RegistrationPaymentAllocator::class')
        ->toContain('itemData')
        ->and($editPage)
        ->toContain('RegistrationPaymentAllocator::class')
        ->toContain('itemsFormState');

    expect($campistasItemTable)
        ->toContain('applyPaymentEligibilityQuery')
        ->toContain("->select(['id', 'nome', 'avatar_url', 'form_data', 'status', 'forma_pagamento', 'dia_pagamento', 'presenca', 'tribo_id'])")
        ->not->toContain('array_keys(app(RegistrationPaymentAllocator::class)->registrationOptions')
        ->and($equipeTrabalhoItemTable)
        ->toContain('applyPaymentEligibilityQuery')
        ->toContain("->select(['id', 'nome', 'avatar_url', 'data_form', 'status', 'tribo_id', 'descricao', 'tipo_equipe'])")
        ->not->toContain('array_keys(app(RegistrationPaymentAllocator::class)->registrationOptions')
        ->toContain('EquipeTrabalhoTable::getColumns()')
        ->toContain('EquipeTrabalhoTable::getFilters()');

    expect(Schema::getColumnType('lancamentos', 'descricao'))->toBe('text');
});

it('filters registration options in sql instead of checking each registration individually', function () {
    seedFinancialItemSettings(25000, teamInternalAmount: 12000, teamExternalAmount: 8000);
    CategoriaLancamento::ensureSystemDefaults();

    $category = financialItemRegistrationCategory(Campista::class);
    $available = collect(range(1, 30))
        ->map(fn (int $number): Campista => financialItemCampista("Disponível {$number}"));
    $linked = financialItemCampista('Já vinculado SQL');

    app(RegistrationPaymentAllocator::class)->syncItems(financialItemEntry(), [
        financialItemPayload($category, $linked),
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $results = app(RegistrationPaymentAllocator::class)->registrationOptions(Campista::class);

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($results)
        ->toHaveKey($available->first()->id)
        ->not->toHaveKey($linked->id)
        ->and($queryCount)->toBeLessThan(20);
});

it('renders selected campista registration as a compact card on the edit form', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $campista = Campista::factory()->create([
        'nome' => 'Maria Selecionada Edit',
        'avatar_url' => 'foto-formulario/maria-edit.png',
        'status' => StatusInscricao::Pago,
    ]);

    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Inscrição Edit',
        'tipo' => TipoLacamento::Receita,
        'ativo' => true,
    ]);

    $lancamento = financialItemEntry([
        'nome' => 'Lançamento campista selecionada',
        'tipo' => TipoLacamento::Receita,
    ]);
    $lancamento->items()->create(financialItemPayload($category, $campista, [
        'valor' => 35000,
    ]));

    $this->actingAs($user)
        ->get(route('filament.admin.resources.lancamentos.edit', ['record' => $lancamento]))
        ->assertOk()
        ->assertSee('<div class="juvenil-launch-registration-card">', false)
        ->assertSee('Campista #'.$campista->id)
        ->assertSee('Maria Selecionada Edit')
        ->assertSee('Pago')
        ->assertSee('/storage/foto-formulario/maria-edit.png', false)
        ->assertDontSee('Campista #'.$campista->id.' - Maria Selecionada Edit');
});

it('renders selected team work registration as a compact card on the edit form', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $member = EquipeTrabalho::factory()->create([
        'nome' => 'Servo Selecionado Edit',
        'avatar_url' => 'foto-formulario-equipe-trabalho/servo-edit.png',
        'descricao' => 'Liturgia',
        'status' => StatusInscricaoEquipeTrabalho::Aprovado,
        'tipo_equipe' => TipoEquipeTrabalho::Interna,
    ]);

    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Contribuição Edit',
        'tipo' => TipoLacamento::Receita,
        'ativo' => true,
    ]);

    $lancamento = financialItemEntry([
        'nome' => 'Lançamento equipe selecionada',
        'tipo' => TipoLacamento::Receita,
    ]);
    $lancamento->items()->create(financialItemPayload($category, $member, [
        'valor' => 4400,
    ]));

    $this->actingAs($user)
        ->get(route('filament.admin.resources.lancamentos.edit', ['record' => $lancamento]))
        ->assertOk()
        ->assertSee('<div class="juvenil-launch-registration-card">', false)
        ->assertSee('Equipe #'.$member->id)
        ->assertSee('Servo Selecionado Edit')
        ->assertSee('Liturgia')
        ->assertSee('Interna')
        ->assertSee('Aprovado')
        ->assertSee('/storage/foto-formulario-equipe-trabalho/servo-edit.png', false)
        ->assertDontSee('valor R$ 44,00');
});

it('renders the financial item total with sign and semantic color', function () {
    $form = file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php'));
    $valueFieldBlock = Str::between($form, "TextInput::make('valor')", "Select::make('categoria_lancamento_id')");

    expect((string) LancamentoForm::itemsTotalHtml([], TipoLacamento::Receita->value))
        ->toContain('Total do lançamento')
        ->toContain('R$ 0,00')
        ->toContain('x-data')
        ->toContain('x-text="formattedTotal"')
        ->toContain('data-lancamento-total')
        ->toContain('flex-col')
        ->toContain('rounded-lg bg-white/5')
        ->toContain('text-sm font-semibold leading-none text-white')
        ->toContain('text-[2.75rem] font-black leading-none tracking-normal')
        ->toContain('x-bind:style="valueStyle"')
        ->toContain('style="color: '.Color::Green[400].';"')
        ->not->toContain('border-white')
        ->and((string) LancamentoForm::itemsTotalHtml([
            ['valor' => 25000],
            ['valor' => '100,00'],
        ], TipoLacamento::Receita->value))
        ->toContain('R$ 350,00')
        ->toContain('style="color: '.Color::Green[400].';"')
        ->not->toContain('text-emerald')
        ->not->toContain('border-emerald')
        ->and((string) LancamentoForm::itemsTotalHtml([
            ['valor' => 25000],
            ['valor' => '100,00'],
        ], TipoLacamento::Despesa->value))
        ->toContain('-R$ 350,00')
        ->toContain('style="color: '.Color::Red[400].';"')
        ->not->toContain('text-rose')
        ->not->toContain('border-rose')
        ->and((string) LancamentoForm::itemsTotalHtml([
            ['valor' => 25000],
            ['valor' => '100,00'],
        ], TipoLacamento::Doacao->value))
        ->toContain('R$ 350,00')
        ->toContain('style="color: '.Color::Blue[400].';"')
        ->not->toContain('text-sky')
        ->not->toContain('border-sky')
        ->and($valueFieldBlock)
        ->not->toContain('->live()');
});

it('hides registration linking fields for expense financial items', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Despesa filtro',
        'tipo' => TipoLacamento::Despesa->value,
    ]);

    Livewire::test(CreateLancamento::class)
        ->fillForm(financialItemFormPayload([
            'nome' => 'Despesa sem vínculo',
            'tipo' => TipoLacamento::Despesa->value,
            'comprador' => 'Coordenação',
            'items' => [
                [
                    'nome' => 'Compra de materiais',
                    'valor' => 35000,
                    'categoria_lancamento_id' => $category->id,
                    'registration_type' => Campista::class,
                    'registration_id' => 10,
                    'descricao' => null,
                ],
            ],
        ]))
        ->assertSee('-R$ 350,00')
        ->assertDontSee('Tipo da inscrição');
});

it('fills the item amount from the selected category default value only when configured', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $categoryWithDefault = CategoriaLancamento::factory()->create([
        'nome' => 'Camiseta com valor padrão',
        'tipo' => TipoLacamento::Receita->value,
        'valor_padrao' => 4500,
    ]);

    $categoryWithoutDefault = CategoriaLancamento::factory()->create([
        'nome' => 'Categoria sem valor padrão',
        'tipo' => TipoLacamento::Receita->value,
        'valor_padrao' => 0,
    ]);

    Livewire::test(CreateLancamento::class)
        ->fillForm(financialItemFormPayload([
            'tipo' => TipoLacamento::Receita->value,
            'items' => [
                [
                    'nome' => 'Camiseta',
                    'valor' => 1000,
                    'categoria_lancamento_id' => null,
                    'registration_type' => null,
                    'registration_id' => null,
                    'descricao' => null,
                ],
            ],
        ]))
        ->set('data.items.0.categoria_lancamento_id', $categoryWithDefault->id)
        ->assertFormSet(fn (array $state): array => tap([], function () use ($state): void {
            expect(data_get($state, 'items.0.valor'))->toBe('45,00');
        }))
        ->set('data.items.0.valor', '12,34')
        ->set('data.items.0.categoria_lancamento_id', $categoryWithoutDefault->id)
        ->assertFormSet(fn (array $state): array => tap([], function () use ($state): void {
            expect(data_get($state, 'items.0.valor'))->toBe('12,34');
        }));
});

it('fills system category amounts from configured registration values', function () {
    $this->seed(ShieldSeeder::class);
    seedFinancialItemSettings(32550, 4400, 3500);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    CategoriaLancamento::ensureSystemDefaults();

    $campista = financialItemCampista('Campista Valor Configurado');
    $internalTeam = EquipeTrabalho::factory()->create([
        'nome' => 'Equipe Interna Valor',
        'tipo_equipe' => TipoEquipeTrabalho::Interna->value,
    ]);
    $externalTeam = EquipeTrabalho::factory()->create([
        'nome' => 'Equipe Externa Valor',
        'tipo_equipe' => TipoEquipeTrabalho::Externa->value,
    ]);
    $campistaCategory = financialItemRegistrationCategory(Campista::class);
    $teamCategory = financialItemRegistrationCategory(EquipeTrabalho::class);

    expect(LancamentoForm::categoryDefaultValueForInput(
        TipoLacamento::Receita->value,
        $teamCategory->id,
        EquipeTrabalho::class,
        $internalTeam->id,
    ))->toBe('44,00');

    Livewire::test(CreateLancamento::class)
        ->fillForm(financialItemFormPayload([
            'tipo' => TipoLacamento::Receita->value,
            'items' => [
                [
                    'nome' => 'Vínculo configurado',
                    'valor' => 0,
                    'categoria_lancamento_id' => null,
                    'registration_type' => Campista::class,
                    'registration_id' => $campista->id,
                    'descricao' => null,
                ],
            ],
        ]))
        ->set('data.items.0.categoria_lancamento_id', $campistaCategory->id)
        ->assertFormSet(fn (array $state): array => tap([], function () use ($state): void {
            expect(data_get($state, 'items.0.valor'))->toBe('325,50');
        }));

    Livewire::test(CreateLancamento::class)
        ->fillForm(financialItemFormPayload([
            'tipo' => TipoLacamento::Receita->value,
            'items' => [
                [
                    'nome' => 'Equipe configurada',
                    'valor' => 0,
                    'categoria_lancamento_id' => null,
                    'registration_type' => EquipeTrabalho::class,
                    'registration_id' => $internalTeam->id,
                    'descricao' => null,
                ],
            ],
        ]))
        ->set('data.items.0.valor', '12,34')
        ->set('data.items.0.categoria_lancamento_id', $teamCategory->id)
        ->set('data.items.0.registration_id', $internalTeam->id)
        ->assertFormSet(fn (array $state): array => tap([], function () use ($state): void {
            expect(data_get($state, 'items.0.valor'))->toBe('44,00');
        }))
        ->set('data.items.0.valor', '12,34')
        ->set('data.items.0.registration_id', $externalTeam->id)
        ->assertFormSet(fn (array $state): array => tap([], function () use ($state): void {
            expect(data_get($state, 'items.0.valor'))->toBe('35,00');
        }));

    expect(LancamentoForm::categoryDefaultValueForInput(
        TipoLacamento::Receita->value,
        $teamCategory->id,
        EquipeTrabalho::class,
        null,
    ))->toBeNull();

    Livewire::test(CreateLancamento::class)
        ->fillForm(financialItemFormPayload([
            'tipo' => TipoLacamento::Receita->value,
            'items' => [
                [
                    'nome' => 'Equipe sem vínculo',
                    'valor' => 1234,
                    'categoria_lancamento_id' => null,
                    'registration_type' => EquipeTrabalho::class,
                    'registration_id' => null,
                    'descricao' => null,
                ],
            ],
        ]))
        ->set('data.items.0.valor', '12,34')
        ->set('data.items.0.categoria_lancamento_id', $teamCategory->id)
        ->assertFormSet(fn (array $state): array => tap([], function () use ($state): void {
            expect(data_get($state, 'items.0.valor'))->toBe('12,34');
        }));
});

it('keeps financial items when changing type and only clears item categories', function () {
    $this->seed(ShieldSeeder::class);
    CategoriaLancamento::ensureSystemDefaults();

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $revenueCategory = CategoriaLancamento::query()
        ->where('tipo', TipoLacamento::Receita->value)
        ->firstOrFail();

    $undoRepeaterFake = Repeater::fake();

    try {
        Livewire::test(CreateLancamento::class)
            ->fillForm(financialItemFormPayload([
                'tipo' => TipoLacamento::Receita->value,
                'items' => [
                    [
                        'nome' => 'Primeiro item preservado',
                        'valor' => 12500,
                        'categoria_lancamento_id' => $revenueCategory->id,
                        'registration_type' => null,
                        'registration_id' => null,
                        'descricao' => 'Descrição preservada',
                    ],
                    [
                        'nome' => 'Segundo item preservado',
                        'valor' => 25000,
                        'categoria_lancamento_id' => $revenueCategory->id,
                        'registration_type' => null,
                        'registration_id' => null,
                        'descricao' => null,
                    ],
                ],
            ]))
            ->set('data.tipo', TipoLacamento::Despesa->value)
            ->assertFormSet(function (array $state): array {
                expect($state['items'])
                    ->toHaveCount(2)
                    ->and(data_get($state, 'items.0.nome'))->toBe('Primeiro item preservado')
                    ->and(data_get($state, 'items.0.valor'))->toBe(12500)
                    ->and(data_get($state, 'items.0.categoria_lancamento_id'))->toBeNull()
                    ->and(data_get($state, 'items.0.descricao'))->toBe('Descrição preservada')
                    ->and(data_get($state, 'items.1.nome'))->toBe('Segundo item preservado')
                    ->and(data_get($state, 'items.1.valor'))->toBe(25000)
                    ->and(data_get($state, 'items.1.categoria_lancamento_id'))->toBeNull();

                return [];
            });
    } finally {
        $undoRepeaterFake();
    }
});

it('filters financial entries by type registration link name and creation period', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Inscrições filtro',
        'tipo' => TipoLacamento::Receita->value,
    ]);
    $campista = financialItemCampista('Alice Filtro Campista');
    $equipe = EquipeTrabalho::factory()->create([
        'nome' => 'Bruno Filtro Equipe',
    ]);

    $campistaLaunch = financialItemEntry([
        'nome' => 'Recebimento Alice',
        'created_at' => '2026-06-10 10:00:00',
    ]);
    $campistaLaunch->items()->create(financialItemPayload($category, $campista, [
        'valor' => 35000,
    ]));

    $equipeLaunch = financialItemEntry([
        'nome' => 'Recebimento equipe',
        'created_at' => '2026-06-11 10:00:00',
    ]);
    $equipeLaunch->items()->create(financialItemPayload($category, $equipe, [
        'valor' => 35000,
    ]));

    $unlinkedLaunch = financialItemEntry([
        'nome' => 'Despesa avulsa filtro',
        'tipo' => TipoLacamento::Despesa->value,
        'created_at' => '2026-05-10 10:00:00',
    ]);
    $unlinkedLaunch->items()->create([
        'nome' => 'Material avulso',
        'valor' => 12000,
        'categoria_lancamento_id' => $category->id,
        'registration_type' => null,
        'registration_id' => null,
        'descricao' => null,
    ]);

    Livewire::test(ListLancamentos::class)
        ->assertTableFilterExists('tipo')
        ->assertTableFilterExists('registration_link')
        ->assertTableFilterExists('name_search')
        ->assertTableFilterExists('created_at')
        ->filterTable('tipo', TipoLacamento::Despesa->value)
        ->assertCanSeeTableRecords([$unlinkedLaunch])
        ->assertCanNotSeeTableRecords([$campistaLaunch, $equipeLaunch]);

    Livewire::test(ListLancamentos::class)
        ->filterTable('registration_link', ['linked' => 'linked'])
        ->assertCanSeeTableRecords([$campistaLaunch, $equipeLaunch])
        ->assertCanNotSeeTableRecords([$unlinkedLaunch]);

    Livewire::test(ListLancamentos::class)
        ->filterTable('registration_link', [
            'linked' => 'linked',
            'registration_type' => Campista::class,
        ])
        ->assertCanSeeTableRecords([$campistaLaunch])
        ->assertCanNotSeeTableRecords([$equipeLaunch, $unlinkedLaunch]);

    Livewire::test(ListLancamentos::class)
        ->filterTable('name_search', ['search' => 'Bruno Filtro'])
        ->assertCanSeeTableRecords([$equipeLaunch])
        ->assertCanNotSeeTableRecords([$campistaLaunch, $unlinkedLaunch]);

    Livewire::test(ListLancamentos::class)
        ->filterTable('created_at', [
            'created_from' => '2026-06-01',
            'created_until' => '2026-06-30',
        ])
        ->assertCanSeeTableRecords([$campistaLaunch, $equipeLaunch])
        ->assertCanNotSeeTableRecords([$unlinkedLaunch]);
});

it('uses a spacious responsive layout for financial item fields', function () {
    $form = file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php'));
    $totalPreviewPosition = strpos($form, "Html::make(fn (Get \$get): HtmlString => self::itemsTotalHtml(\$get('items') ?? [], \$get('tipo')))");
    $itemsSectionPosition = strpos($form, "Section::make('Itens do lançamento')");
    $totalPreviewBlock = Str::between($form, "Html::make(fn (Get \$get): HtmlString => self::itemsTotalHtml(\$get('items') ?? [], \$get('tipo')))", "RichEditor::make('descricao')");
    $valueFieldBlock = Str::between($form, "TextInput::make('valor')", "Select::make('categoria_lancamento_id')");

    expect($form)
        ->toContain("Repeater::make('items')")
        ->toContain("->columns([\n                                    'default' => 1,\n                                    'md' => 2,\n                                    'xl' => 12,\n                                ])")
        ->toContain("'xl' => 4")
        ->toContain("'xl' => 12");

    expect($totalPreviewBlock)
        ->toContain("->columnStart([\n                                    'md' => 2,\n                                    'xl' => 9,\n                                ])");

    expect($valueFieldBlock)
        ->toContain("'xl' => 4")
        ->not->toContain("'xl' => 2");

    expect($totalPreviewPosition)
        ->not->toBeFalse()
        ->toBeLessThan($itemsSectionPosition);
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

function seedFinancialItemSettings(int $amount, ?int $teamInternalAmount = null, ?int $teamExternalAmount = null): void
{
    foreach ([
        'valor_acampamento' => $amount,
        'valor_equipe_trabalho_interna' => $teamInternalAmount ?? $amount,
        'valor_equipe_trabalho_externa' => $teamExternalAmount ?? $amount,
    ] as $name => $value) {
        DB::table('settings')->updateOrInsert(
            [
                'group' => 'general',
                'name' => $name,
            ],
            [
                'payload' => json_encode($value),
            ],
        );
    }
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
