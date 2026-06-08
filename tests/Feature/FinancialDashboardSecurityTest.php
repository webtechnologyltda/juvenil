<?php

use App\Enums\RoleEnum;
use App\Filament\Pages\FinancialDashboard;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

it('creates a finance role with financial dashboard and read permissions only', function () {
    $this->seed(ShieldSeeder::class);

    $role = Role::findByName('Financeiro');

    expect($role->id)->toBe(RoleEnum::Financeiro->value)
        ->and($role->hasPermissionTo('page_financial_dashboard'))->toBeTrue()
        ->and($role->hasPermissionTo('view_any_lancamento'))->toBeTrue()
        ->and($role->hasPermissionTo('view_lancamento'))->toBeTrue()
        ->and($role->hasPermissionTo('view_any_categoria_lancamento'))->toBeTrue()
        ->and($role->hasPermissionTo('view_categoria_lancamento'))->toBeTrue()
        ->and($role->hasPermissionTo('view_sensitive_health_campista'))->toBeFalse();
});

it('allows finance users to access the financial dashboard without exposing the operational dashboard', function () {
    $this->seed(ShieldSeeder::class);

    $financeUser = User::factory()->create();
    $financeUser->assignRole('Financeiro');

    $commonUser = User::factory()->create();

    $this->actingAs($financeUser)
        ->get(FinancialDashboard::getUrl())
        ->assertOk();

    $this->actingAs($commonUser)
        ->get(FinancialDashboard::getUrl())
        ->assertForbidden();
});

it('repairs existing environments missing the finance permission group when migrations run', function () {
    $this->seed(ShieldSeeder::class);

    $role = Role::findByName('Financeiro');
    $tableNames = config('permission.table_names');
    $rolePivotKey = config('permission.column_names.role_pivot_key') ?? 'role_id';

    DB::table($tableNames['model_has_roles'])
        ->where($rolePivotKey, $role->id)
        ->delete();
    DB::table($tableNames['role_has_permissions'])
        ->where($rolePivotKey, $role->id)
        ->delete();

    $role->delete();

    Permission::query()
        ->whereIn('name', [
            'page_financial_dashboard',
            'view_any_lancamento',
            'view_lancamento',
            'view_any_categoria_lancamento',
            'view_categoria_lancamento',
        ])
        ->delete();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $migrationFiles = glob(database_path('migrations/*_sync_permission_groups_for_existing_environments.php'));

    expect($migrationFiles)->toHaveCount(1);

    $migration = include $migrationFiles[0];
    $migration->up();

    $role = Role::findByName('Financeiro');

    expect($role->id)->toBe(RoleEnum::Financeiro->value)
        ->and($role->hasPermissionTo('page_financial_dashboard'))->toBeTrue()
        ->and($role->hasPermissionTo('view_any_lancamento'))->toBeTrue()
        ->and($role->hasPermissionTo('view_lancamento'))->toBeTrue()
        ->and($role->hasPermissionTo('view_any_categoria_lancamento'))->toBeTrue()
        ->and($role->hasPermissionTo('view_categoria_lancamento'))->toBeTrue();
});
