<?php

use App\Filament\Exports\EquipeTrabalhoExporter;
use App\Filament\Resources\EquipeTrabalhoResource\Pages\ListEquipeTrabalhos;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

it('exports the work team name column with a generic heading', function () {
    $nameColumn = collect(EquipeTrabalhoExporter::getColumns())
        ->first(fn ($column): bool => $column->getName() === 'nome');

    expect($nameColumn)
        ->not->toBeNull()
        ->getLabel()->toBe('Nome');
});

it('shows the work team export action to authorized users', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user);

    Livewire::test(ListEquipeTrabalhos::class)
        ->assertActionVisible(TestAction::make('export')->table()->bulk());
});

it('repairs existing environments missing the work team export permission', function () {
    $this->seed(ShieldSeeder::class);

    $role = Role::findByName('Super Administrador');
    $permission = Permission::findByName('export_equipe_trabalho');
    $tableNames = config('permission.table_names');
    $rolePivotKey = config('permission.column_names.role_pivot_key') ?? 'role_id';
    $permissionPivotKey = config('permission.column_names.permission_pivot_key') ?? 'permission_id';

    DB::table($tableNames['role_has_permissions'])
        ->where($rolePivotKey, $role->getKey())
        ->where($permissionPivotKey, $permission->getKey())
        ->delete();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($role->fresh()->hasPermissionTo('export_equipe_trabalho'))->toBeFalse();

    $migrationFiles = glob(database_path('migrations/*_sync_team_work_export_permission.php'));

    expect($migrationFiles)->toHaveCount(1);

    $migration = include $migrationFiles[0];
    $migration->up();

    expect($role->fresh()->hasPermissionTo('export_equipe_trabalho'))->toBeTrue();
});
