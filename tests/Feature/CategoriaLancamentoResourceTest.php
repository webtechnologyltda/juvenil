<?php

use App\Enums\TipoLacamento;
use App\Filament\Forms\Components\IconPicker;
use App\Filament\Resources\CategoriaLancamentoResource;
use App\Filament\Resources\CategoriaLancamentoResource\Pages\EditCategoriaLancamento;
use App\Filament\Tables\Columns\ColoredIconColumn;
use App\Livewire\IconPickerModal;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\User;
use App\Support\IconBadge;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('renders the category registration in the financial panel', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user)
        ->get(CategoriaLancamentoResource::getUrl('index'))
        ->assertOk()
        ->assertSee('Categorias de Lançamento')
        ->assertSee('Nova categoria')
        ->assertSee('Inscrição')
        ->assertSee('Contribuição Equipe de Trabalho');

    $this->actingAs($user)
        ->get(CategoriaLancamentoResource::getUrl('create'))
        ->assertOk()
        ->assertSee('Identificação')
        ->assertSee('Visual')
        ->assertSee('Valor padrão')
        ->assertSee('Categoria ativa');
});

it('stores categories with type default value color icon and active flag', function () {
    $category = CategoriaLancamento::query()->create([
        'nome' => 'Inscrições',
        'tipo' => TipoLacamento::Receita,
        'valor_padrao' => 12500,
        'cor' => '#f46b12',
        'icone' => 'heroicon-o-ticket',
        'ativo' => true,
    ]);

    expect($category->fresh())
        ->nome->toBe('Inscrições')
        ->tipo->toBe(TipoLacamento::Receita)
        ->valor_padrao->toBe(12500)
        ->cor->toBe('#f46b12')
        ->icone->toBe('heroicon-o-ticket')
        ->ativo->toBeTrue();
});

it('creates the protected system launch categories', function () {
    if (! Schema::hasColumn('categorias_lancamento', 'system_key')) {
        $this->fail('The launch categories table must have a system_key column.');
    }

    $inscricao = CategoriaLancamento::query()
        ->where('system_key', 'inscricao')
        ->first();

    $equipe = CategoriaLancamento::query()
        ->where('system_key', 'contribuicao_equipe_trabalho')
        ->first();

    expect($inscricao)
        ->not->toBeNull()
        ->nome->toBe('Inscrição')
        ->tipo->toBe(TipoLacamento::Receita)
        ->ativo->toBeTrue()
        ->and($equipe)
        ->not->toBeNull()
        ->nome->toBe('Contribuição Equipe de Trabalho')
        ->tipo->toBe(TipoLacamento::Receita)
        ->ativo->toBeTrue();
});

it('only allows color and icon changes on system launch categories', function () {
    if (! Schema::hasColumn('categorias_lancamento', 'system_key')) {
        $this->fail('The launch categories table must have a system_key column.');
    }

    $category = CategoriaLancamento::query()
        ->where('system_key', 'inscricao')
        ->firstOrFail();

    $category->fill([
        'nome' => 'Outro nome',
        'tipo' => TipoLacamento::Despesa,
        'valor_padrao' => 99999,
        'ativo' => false,
        'cor' => '#123456',
        'icone' => 'heroicon-o-fire',
    ]);

    $category->save();
    $category->refresh();

    expect($category)
        ->nome->toBe('Inscrição')
        ->tipo->toBe(TipoLacamento::Receita)
        ->valor_padrao->toBe(0)
        ->ativo->toBeTrue()
        ->cor->toBe('#123456')
        ->icone->toBe('heroicon-o-fire');
});

it('shows configured default values for system launch categories', function () {
    $this->seed(ShieldSeeder::class);

    foreach ([
        'valor_acampamento' => 32550,
        'valor_equipe_trabalho_interna' => 12000,
        'valor_equipe_trabalho_externa' => 8000,
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

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user)
        ->get(CategoriaLancamentoResource::getUrl('index'))
        ->assertOk()
        ->assertSee('R$ 325,50')
        ->assertSee('Interna: R$ 120,00 | Externa: R$ 80,00');
});

it('notifies when a launch category is saved from the edit page', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $category = CategoriaLancamento::factory()->create([
        'nome' => 'Camiseta Equipe de Trabalho',
        'tipo' => TipoLacamento::Receita,
        'valor_padrao' => 3800,
        'cor' => '#7500d6',
        'icone' => 'iconpark-tshirt',
        'ativo' => true,
    ]);

    Livewire\Livewire::test(EditCategoriaLancamento::class, ['record' => $category->getKey()])
        ->fillForm([
            'nome' => 'Camiseta Equipe de Trabalho',
            'tipo' => TipoLacamento::Receita->value,
            'valor_padrao' => 3800,
            'cor' => '#7500d6',
            'icone' => 'iconpark-tshirt',
            'ativo' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Categoria de lançamento salva');
});

it('does not allow deleting system launch categories', function () {
    if (! Schema::hasColumn('categorias_lancamento', 'system_key')) {
        $this->fail('The launch categories table must have a system_key column.');
    }

    $systemCategory = CategoriaLancamento::query()
        ->where('system_key', 'inscricao')
        ->firstOrFail();

    $regularCategory = CategoriaLancamento::factory()->create([
        'nome' => 'Transporte',
        'tipo' => TipoLacamento::Despesa,
    ]);

    expect($systemCategory->delete())->toBeFalse()
        ->and(CategoriaLancamento::query()->whereKey($systemCategory->id)->exists())->toBeTrue()
        ->and($regularCategory->delete())->toBeTrue()
        ->and(CategoriaLancamento::query()->whereKey($regularCategory->id)->exists())->toBeFalse();
});

it('locks the system category identity in the Filament resource', function () {
    $resource = file_get_contents(app_path('Filament/Resources/CategoriaLancamentoResource.php'));
    $editPage = file_get_contents(app_path('Filament/Resources/CategoriaLancamentoResource/Pages/EditCategoriaLancamento.php'));
    $policy = file_get_contents(app_path('Policies/CategoriaLancamentoPolicy.php'));

    expect($resource)
        ->toContain('isSystemDefault')
        ->toContain("TextInput::make('nome')")
        ->toContain("ToggleButtons::make('tipo')")
        ->toContain("Money::make('valor_padrao')")
        ->toContain("Toggle::make('ativo')")
        ->toContain('->disabled(fn (?CategoriaLancamento $record): bool => $record?->isSystemDefault() ?? false)')
        ->toContain('->dehydrated(fn (?CategoriaLancamento $record): bool => ! ($record?->isSystemDefault() ?? false))')
        ->toContain('checkIfRecordIsSelectableUsing')
        ->and($editPage)
        ->toContain('isSystemDefault')
        ->toContain('DeleteAction::make()')
        ->and($policy)
        ->toContain('! $categoriaLancamento->isSystemDefault()');
});

it('uses the Acampay icon picker in the launch category form', function () {
    $resource = file_get_contents(app_path('Filament/Resources/CategoriaLancamentoResource.php'));

    expect($resource)
        ->toContain('use App\Filament\Forms\Components\IconPicker;')
        ->toContain("IconPicker::make('icone')")
        ->not->toContain("Select::make('icone')");

    expect(class_exists(IconPicker::class))->toBeTrue()
        ->and(class_exists(IconPickerModal::class))->toBeTrue()
        ->and(view()->exists('filament.forms.components.icon-picker'))->toBeTrue()
        ->and(view()->exists('livewire.icon-picker-modal'))->toBeTrue()
        ->and(trans('icon_picker.title', locale: 'pt_BR'))->toBe('Selecionar ícone');
});

it('renders the copied icon picker modal and dispatches the selected icon', function () {
    $statePath = 'data.icone';

    Livewire\Livewire::withoutLazyLoading()
        ->test(IconPickerModal::class, [
            'statePath' => $statePath,
            'customIcons' => ['heroicon-o-ticket'],
        ])
        ->assertSee('heroicon-o-ticket')
        ->call('select', 'heroicon-o-ticket')
        ->assertDispatched(
            'icon-picker-selected-'.IconPickerModal::eventToken($statePath),
            icon: 'heroicon-o-ticket',
        );
});

it('shows the registered icon and color in the launch category list', function () {
    $resource = file_get_contents(app_path('Filament/Resources/CategoriaLancamentoResource.php'));

    expect($resource)
        ->toContain('use App\Filament\Tables\Columns\ColoredIconColumn;')
        ->toContain("ColoredIconColumn::make('icone')")
        ->not->toContain("TextColumn::make('cor')")
        ->not->toContain('->icon(fn (CategoriaLancamento $record): string => $record->icone');

    expect(class_exists(ColoredIconColumn::class))->toBeTrue()
        ->and(view()->exists('filament.tables.columns.colored-icon-column'))->toBeTrue();

    $columnView = file_get_contents(resource_path('views/filament/tables/columns/colored-icon-column.blade.php'));

    expect($columnView)
        ->toContain('h-8 w-8')
        ->toContain('rounded-lg')
        ->toContain('class="h-[1.1rem] w-[1.1rem]"')
        ->not->toContain('h-14 w-14')
        ->not->toContain('class="h-6 w-6"');

    $category = new CategoriaLancamento([
        'icone' => 'heroicon-o-ticket',
        'cor' => '#f46b12',
    ]);

    $column = ColoredIconColumn::make('icone');

    expect($column->getIconName($category))->toBe('heroicon-o-ticket')
        ->and($column->getBackgroundColor($category))->toBe('#f46b12')
        ->and($column->getIconColor($category))->toBe('#ffffff');
});

it('renders category tiles in launch tables and select options', function () {
    expect(class_exists(IconBadge::class))->toBeTrue();

    $category = new CategoriaLancamento([
        'nome' => 'Transporte',
        'icone' => 'heroicon-o-shopping-cart',
        'cor' => '#4f18ff',
    ]);

    expect((string) IconBadge::tileIcon($category, $category->nome))
        ->toContain('aria-label="Transporte"')
        ->toContain('background-color: #4f18ff')
        ->toContain('width: 2rem')
        ->toContain('height: 2rem')
        ->toContain('width: 1.1rem')
        ->toContain('height: 1.1rem')
        ->toContain('heroicon-o-shopping-cart');

    expect((string) IconBadge::tile($category, $category->nome))
        ->toContain('Transporte')
        ->toContain('background-color: #4f18ff')
        ->toContain('width: 2rem')
        ->toContain('height: 2rem')
        ->toContain('width: 1rem')
        ->toContain('height: 1rem')
        ->not->toContain('width: 2.5rem')
        ->not->toContain('height: 2.5rem')
        ->toContain('heroicon-o-shopping-cart');

    expect(file_get_contents(app_path('Filament/Resources/LancamentoResource.php')))
        ->toContain('use App\Support\IconBadge;')
        ->toContain('IconBadge::tileIcon')
        ->toContain('juvenil-lancamento-table__category-stack')
        ->toContain('juvenil-lancamento-table__category-stack-extra')
        ->toContain('->html()')
        ->toContain('->tooltip(fn (Lancamento $record): string => $record->categories_summary)')
        ->toContain('$extra = $categories->count() - 2')
        ->toContain('$extraBadge = $extra > 0')
        ->not->toContain("->badge()\n                    ->sortable(),\n\n                TextColumn::make('status')");

    expect(file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php')))
        ->toContain('use App\Support\IconBadge;')
        ->toContain('IconBadge::tile($category')
        ->toContain('->allowHtml()');
});

it('links a financial entry to a launch category', function () {
    $category = CategoriaLancamento::query()->create([
        'nome' => 'Alimentação',
        'tipo' => TipoLacamento::Despesa,
        'cor' => '#ef4444',
        'icone' => 'heroicon-o-shopping-cart',
        'ativo' => true,
    ]);

    $lancamento = Lancamento::factory()->create([
        'tipo' => TipoLacamento::Despesa,
        'user_id' => null,
    ]);

    $lancamento->items()->create([
        'nome' => 'Alimentação da equipe',
        'valor' => 5579,
        'categoria_lancamento_id' => $category->id,
    ]);

    expect($lancamento->fresh()->categories->first())
        ->toBeInstanceOf(CategoriaLancamento::class)
        ->nome->toBe('Alimentação');
});

it('exposes the category field on the financial entry form and table', function () {
    expect(file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php')))
        ->toContain("Select::make('categoria_lancamento_id')")
        ->toContain('categoryOptions')
        ->toContain("->where('tipo', (int) \$type)");

    expect(file_get_contents(app_path('Filament/Resources/LancamentoResource.php')))
        ->toContain("TextColumn::make('categories_summary')")
        ->toContain("SelectFilter::make('categoria_lancamento_id')")
        ->toContain('categoryFilterOptions')
        ->toContain('FinancialFilterOptions::categories()')
        ->toContain('->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml())')
        ->toContain('->native(false)')
        ->toContain('->multiple()')
        ->toContain("whereHas('items'")
        ->toContain("whereIn('categoria_lancamento_id'")
        ->toContain("Group::make('batch_code')");

    expect(file_get_contents(app_path('Support/Financeiro/FinancialFilterOptions.php')))
        ->toContain('IconBadge::tile(');
});
