<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    protected const SENSITIVE_HEALTH_PERMISSION = 'view_sensitive_health_campista';

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = static::getPermissionNames();

        static::makeSuperAdminWithPermissions($permissions);
        static::makeAdministratorWithPermissions($permissions);
        static::makeInfirmaryWithPermissions($permissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeSuperAdminWithPermissions(array $permissions): void
    {
        $role = static::firstOrCreateRole(
            config('filament-shield.super_admin.name', 'Super Administrador'),
            RoleEnum::SuperAdministrador->value,
        );

        $role->syncPermissions(static::makePermissionModels($permissions));
    }

    protected static function makeAdministratorWithPermissions(array $permissions): void
    {
        $role = static::firstOrCreateRole(
            RoleEnum::getRoleEnumDescription(RoleEnum::Administrador),
            RoleEnum::Administrador->value,
        );

        $role->syncPermissions(static::makePermissionModels(
            static::administratorPermissionNames($permissions),
        ));
    }

    protected static function makeInfirmaryWithPermissions(array $permissions): void
    {
        $role = static::firstOrCreateRole(
            RoleEnum::getRoleEnumDescription(RoleEnum::Enfermaria),
            RoleEnum::Enfermaria->value,
        );

        $role->syncPermissions(static::makePermissionModels(
            static::infirmaryPermissionNames($permissions),
        ));
    }

    protected static function firstOrCreateRole(string $name, int $id)
    {
        /** @var class-string<Role> $roleModel */
        $roleModel = Utils::getRoleModel();

        $guard = 'web';

        $role = $roleModel::query()
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->first();

        if ($role !== null) {
            return $role;
        }

        $role = new $roleModel;
        $role->forceFill([
            'id' => $id,
            'name' => $name,
            'guard_name' => $guard,
        ]);
        $role->save();

        return $role;
    }

    protected static function makePermissionModels(array $permissions): array
    {
        /** @var class-string<Permission> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $guard = 'web';

        return collect($permissions)
            ->map(fn (string $permission) => $permissionModel::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]))
            ->all();
    }

    protected static function administratorPermissionNames(array $permissions): array
    {
        return collect($permissions)
            ->filter(fn (string $permission): bool => str_ends_with($permission, '_campista')
                || str_ends_with($permission, '_tribo')
                || str_contains($permission, 'dashboard')
                || str_contains($permission, 'operational'))
            ->reject(fn (string $permission): bool => $permission === self::SENSITIVE_HEALTH_PERMISSION
                || str_contains($permission, 'financeiro')
                || str_contains($permission, 'lancamento'))
            ->values()
            ->all();
    }

    protected static function infirmaryPermissionNames(array $permissions): array
    {
        return collect($permissions)
            ->filter(fn (string $permission): bool => in_array($permission, [
                'view_any_campista',
                'view_campista',
                self::SENSITIVE_HEALTH_PERMISSION,
            ], true))
            ->values()
            ->all();
    }

    protected static function getPermissionNames(): array
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return collect()
            ->merge(static::resourcePermissionNames())
            ->merge(static::pagePermissionNames())
            ->merge(static::widgetPermissionNames())
            ->merge(static::customPermissionNames())
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected static function resourcePermissionNames(): Collection
    {
        return collect(FilamentShield::getResources())
            ->flatMap(fn (array $resource): array => collect($resource['permissions'] ?? [])
                ->pluck('key')
                ->all());
    }

    protected static function pagePermissionNames(): Collection
    {
        return collect(FilamentShield::getPages())
            ->flatMap(fn (array $page): array => array_keys($page['permissions'] ?? []));
    }

    protected static function widgetPermissionNames(): Collection
    {
        return collect(FilamentShield::getWidgets())
            ->flatMap(fn (array $widget): array => array_keys($widget['permissions'] ?? []));
    }

    protected static function customPermissionNames(): array
    {
        return [
            'audit_campista',
            'audit_tribo',
            'export_campista',
            'restoreAudit_campista',
            'restoreAudit_tribo',
            'updateTribo_campista',
            self::SENSITIVE_HEALTH_PERMISSION,
        ];
    }
}
