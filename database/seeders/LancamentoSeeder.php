<?php

namespace Database\Seeders;

use App\Models\Lancamento;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LancamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Lancamento::factory(10)->create();
    }
}
