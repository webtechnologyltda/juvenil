<?php

namespace Database\Seeders;

use App\Models\EquipeTrabalho;
use Database\Seeders\Support\DemoRegistrationData;
use Illuminate\Database\Seeder;

class EquipeTrabalhoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EquipeTrabalho::query()
            ->where('descricao', DemoRegistrationData::DEMO_OBSERVATION)
            ->delete();

        foreach (range(1, DemoRegistrationData::EQUIPE_TRABALHO_TOTAL) as $index) {
            EquipeTrabalho::unguarded(fn () => EquipeTrabalho::query()->create(
                DemoRegistrationData::equipeTrabalhoAttributes($index),
            ));
        }
    }
}
