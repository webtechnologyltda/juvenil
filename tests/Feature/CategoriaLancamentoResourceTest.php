<?php

use App\Enums\TipoLacamento;
use App\Filament\Forms\Components\IconPicker;
use App\Filament\Resources\CategoriaLancamentoResource;
use App\Livewire\IconPickerModal;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\User;
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
