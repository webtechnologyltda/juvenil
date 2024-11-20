<?php

namespace Database\Seeders;

use App\Models\Tribo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TriboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tribo::factory()->count(10)->create();
    }
}
