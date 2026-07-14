<?php

use Database\Seeders\ShieldSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        if (! Schema::hasTable($tableNames['roles'] ?? 'roles')
            || ! Schema::hasTable($tableNames['permissions'] ?? 'permissions')) {
            return;
        }

        ShieldSeeder::syncRolesAndPermissions();
    }

    public function down(): void
    {
        //
    }
};
