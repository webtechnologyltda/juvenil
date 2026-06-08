<?php

use App\Enums\RoleEnum;
use App\Filament\Pages\FinancialDashboard;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

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
