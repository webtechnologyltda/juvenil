<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin'),
        ]);
        if (app()->environment('local')) {
            $this->call([
                UserSeeder::class,
                TriboSeeder::class,
                CampistaSeeder::class,
                LancamentoSeeder::class,
            ]);
        }
        $this->call([
            ShieldSeeder::class
        ]);

        $admin->assignRole('Super Administrador');

    }
}
