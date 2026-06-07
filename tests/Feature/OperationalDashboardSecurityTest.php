<?php

use App\Enums\RoleEnum;
use App\Models\Campista;
use App\Models\User;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds administrator and infirmary roles with least privilege health permissions', function () {
    $this->seed(ShieldSeeder::class);

    expect(Role::query()->where('name', 'Administrador')->exists())->toBeTrue()
        ->and(Role::query()->where('name', 'Enfermaria')->exists())->toBeTrue()
        ->and(Role::findByName('Administrador')->id)->toBe(RoleEnum::Administrador->value)
        ->and(Role::findByName('Enfermaria')->id)->toBe(RoleEnum::Enfermaria->value)
        ->and(Permission::query()->where('name', 'view_sensitive_health_campista')->exists())->toBeTrue()
        ->and(Role::findByName('Administrador')->hasPermissionTo('view_sensitive_health_campista'))->toBeFalse()
        ->and(Role::findByName('Enfermaria')->hasPermissionTo('view_sensitive_health_campista'))->toBeTrue()
        ->and(Role::findByName('Super Administrador')->hasPermissionTo('view_sensitive_health_campista'))->toBeTrue();
});

it('exposes role enum labels for administrator and infirmary', function () {
    expect(RoleEnum::getRoleEnum(4))->toBe(RoleEnum::Administrador)
        ->and(RoleEnum::getRoleEnum(5))->toBe(RoleEnum::Enfermaria)
        ->and(RoleEnum::getRoleEnumDescriptionById(4))->toBe('Administrador')
        ->and(RoleEnum::getRoleEnumDescriptionById(5))->toBe('Enfermaria')
        ->and(RoleEnum::getRoleEnumDescription(RoleEnum::Administrador))->toBe('Administrador')
        ->and(RoleEnum::getRoleEnumDescription(RoleEnum::Enfermaria))->toBe('Enfermaria');
});

it('authorizes sensitive health policy only for super administrator and infirmary', function () {
    $this->seed(ShieldSeeder::class);

    $superAdministrator = User::factory()->create();
    $administrator = User::factory()->create();
    $infirmary = User::factory()->create();

    $superAdministrator->assignRole('Super Administrador');
    $administrator->assignRole('Administrador');
    $infirmary->assignRole('Enfermaria');

    expect(Gate::forUser($superAdministrator)->allows('viewSensitiveHealth', Campista::class))->toBeTrue()
        ->and(Gate::forUser($infirmary)->allows('viewSensitiveHealth', Campista::class))->toBeTrue()
        ->and(Gate::forUser($administrator)->allows('viewSensitiveHealth', Campista::class))->toBeFalse();
});
