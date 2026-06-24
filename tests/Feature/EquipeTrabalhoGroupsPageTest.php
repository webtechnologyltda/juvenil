<?php

use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\TipoEquipeTrabalho;
use App\Filament\Pages\EquipeTrabalhoGroups;
use App\Models\EquipeTrabalho;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('registers the team work group page in the camp management sidebar', function () {
    $page = file_get_contents(app_path('Filament/Pages/EquipeTrabalhoGroups.php'));
    $view = file_get_contents(resource_path('views/filament/pages/equipe-trabalho-groups.blade.php'));

    expect($page)
        ->toContain("protected static ?string \$slug = 'equipe-trabalho-grupos';")
        ->toContain("protected static ?string \$navigationLabel = 'Grupos de Equipe';")
        ->toContain("\$navigationGroup = 'Gestão Acampamento';")
        ->toContain('Heroicon::OutlinedRectangleGroup')
        ->toContain('HasPageShield')
        ->toContain('InteractsWithTable')
        ->and($view)
        ->toContain('{{ $this->table }}');
});

it('grants the team work groups page permission to camp administrators', function () {
    $this->seed(ShieldSeeder::class);

    expect(Role::findByName('Administrador')->hasPermissionTo('page_equipe_trabalho_groups'))->toBeTrue()
        ->and(Role::findByName('Financeiro')->hasPermissionTo('page_equipe_trabalho_groups'))->toBeFalse();
});

it('renders work team groups with their value rule and configured reference amount', function () {
    seedEquipeTrabalhoGroupSettings(internalAmount: 12000, externalAmount: 8000);
    seedEquipeTrabalhoGroup('Cozinha', TipoEquipeTrabalho::Interna, members: 2);
    seedEquipeTrabalhoGroup('Externa', TipoEquipeTrabalho::Externa);

    $this->seed(ShieldSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $this->get(EquipeTrabalhoGroups::getUrl())
        ->assertOk()
        ->assertSee('Grupos de Equipe de Trabalho')
        ->assertSee('Cozinha')
        ->assertSee('Externa')
        ->assertSee('Interna')
        ->assertSee('R$ 120,00')
        ->assertSee('R$ 80,00');
});

it('updates a work team group name and value rule for every member in that group', function () {
    seedEquipeTrabalhoGroupSettings(internalAmount: 12000, externalAmount: 8000);
    seedEquipeTrabalhoGroup('Cozinha', TipoEquipeTrabalho::Interna, members: 2);
    seedEquipeTrabalhoGroup('Missão', TipoEquipeTrabalho::Interna);

    $this->seed(ShieldSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    Livewire::test(EquipeTrabalhoGroups::class)
        ->assertSee('Cozinha')
        ->callTableAction('edit', sha1('Cozinha'), [
            'nome' => 'Cozinha Geral',
            'tipo_equipe' => TipoEquipeTrabalho::Externa->value,
        ])
        ->assertHasNoTableActionErrors()
        ->assertSee('Cozinha Geral');

    expect(EquipeTrabalho::query()
        ->where('descricao', 'Cozinha Geral')
        ->orderBy('id')
        ->pluck('tipo_equipe')
        ->all())->toBe([
            TipoEquipeTrabalho::Externa,
            TipoEquipeTrabalho::Externa,
        ])
        ->and(EquipeTrabalho::query()->where('descricao', 'Cozinha')->exists())->toBeFalse()
        ->and(EquipeTrabalho::query()->where('descricao', 'Missão')->count())->toBe(1);
});

it('rejects renaming a work team group to an existing group name', function () {
    seedEquipeTrabalhoGroup('Cozinha', TipoEquipeTrabalho::Interna);
    seedEquipeTrabalhoGroup('Missão', TipoEquipeTrabalho::Externa);

    $this->seed(ShieldSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    Livewire::test(EquipeTrabalhoGroups::class)
        ->callTableAction('edit', sha1('Cozinha'), [
            'nome' => 'Missão',
            'tipo_equipe' => TipoEquipeTrabalho::Externa->value,
        ])
        ->assertHasTableActionErrors(['nome']);

    expect(EquipeTrabalho::query()->where('descricao', 'Cozinha')->count())->toBe(1)
        ->and(EquipeTrabalho::query()->where('descricao', 'Missão')->count())->toBe(1);
});

function seedEquipeTrabalhoGroup(string $group, TipoEquipeTrabalho $type, int $members = 1): void
{
    foreach (range(1, $members) as $member) {
        EquipeTrabalho::factory()->create([
            'nome' => sprintf('%s Integrante %02d', $group, $member),
            'descricao' => $group,
            'status' => StatusInscricaoEquipeTrabalho::Pendente->value,
            'tipo_equipe' => $type->value,
        ]);
    }
}

function seedEquipeTrabalhoGroupSettings(int $internalAmount, int $externalAmount): void
{
    foreach ([
        'valor_equipe_trabalho_interna' => $internalAmount,
        'valor_equipe_trabalho_externa' => $externalAmount,
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
