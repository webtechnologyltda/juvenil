<?php

use App\Enums\TipoLacamento;
use App\Filament\Forms\Components\IconPicker;
use App\Filament\Resources\CategoriaLancamentoResource;
use App\Filament\Tables\Columns\ColoredIconColumn;
use App\Livewire\IconPickerModal;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\User;
use App\Support\IconBadge;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        ->assertSee('Nenhuma categoria cadastrada');

    $this->actingAs($user)
        ->get(CategoriaLancamentoResource::getUrl('create'))
        ->assertOk()
        ->assertSee('Identificação')
        ->assertSee('Visual')
        ->assertSee('Categoria ativa');
});

it('stores categories with type color icon and active flag', function () {
    $category = CategoriaLancamento::query()->create([
        'nome' => 'Inscrições',
        'tipo' => TipoLacamento::Receita,
        'cor' => '#f46b12',
        'icone' => 'heroicon-o-ticket',
        'ativo' => true,
    ]);

    expect($category->fresh())
        ->nome->toBe('Inscrições')
        ->tipo->toBe(TipoLacamento::Receita)
        ->cor->toBe('#f46b12')
        ->icone->toBe('heroicon-o-ticket')
        ->ativo->toBeTrue();
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
        ->toContain('h-14 w-14')
        ->toContain('rounded-xl')
        ->toContain('class="h-6 w-6"');

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
        ->toContain('width: 3.5rem')
        ->toContain('height: 3.5rem')
        ->toContain('heroicon-o-shopping-cart');

    expect((string) IconBadge::tile($category, $category->nome))
        ->toContain('Transporte')
        ->toContain('background-color: #4f18ff')
        ->toContain('heroicon-o-shopping-cart');

    expect(file_get_contents(app_path('Filament/Resources/LancamentoResource.php')))
        ->toContain('use App\Support\IconBadge;')
        ->toContain('IconBadge::tileIcon')
        ->toContain('->html()')
        ->toContain('->tooltip(fn (?string $state): string => $state ?? \'Sem categoria\')')
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
        'categoria_lancamento_id' => $category->id,
        'user_id' => null,
    ]);

    expect($lancamento->fresh()->categoria)
        ->toBeInstanceOf(CategoriaLancamento::class)
        ->nome->toBe('Alimentação');
});

it('exposes the category field on the financial entry form and table', function () {
    expect(file_get_contents(app_path('Filament/Resources/LancamentoResource/Forms/LancamentoForm.php')))
        ->toContain("Select::make('categoria_lancamento_id')")
        ->toContain('categoryOptions')
        ->toContain("->where('tipo', (int) \$type)");

    expect(file_get_contents(app_path('Filament/Resources/LancamentoResource.php')))
        ->toContain("TextColumn::make('categoria.nome')")
        ->toContain("SelectFilter::make('categoria_lancamento_id')")
        ->toContain("Group::make('categoria.nome')");
});
