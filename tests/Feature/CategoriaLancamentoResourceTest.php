<?php

use App\Enums\TipoLacamento;
use App\Filament\Resources\CategoriaLancamentoResource;
use App\Filament\Resources\LancamentoResource;
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
