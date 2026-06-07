<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        static::makeSuperAdminWithPermissions(static::getPermissionNames());

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeSuperAdminWithPermissions(array $permissions): void
    {
        /** @var class-string<\Spatie\Permission\Models\Role> $roleModel */
        $roleModel = Utils::getRoleModel();

        /** @var class-string<\Spatie\Permission\Models\Permission> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $guard = 'web';

        $role = $roleModel::firstOrCreate([
            'name' => config('filament-shield.super_admin.name', 'Super Administrador'),
            'guard_name' => $guard,
        ]);

        $permissionModels = collect($permissions)
            ->map(fn (string $permission) => $permissionModel::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guard,
            ]))
            ->all();

        $role->syncPermissions($permissionModels);
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
        ];
    }
}
