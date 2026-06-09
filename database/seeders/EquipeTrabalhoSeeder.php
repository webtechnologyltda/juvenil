<?php

namespace Database\Seeders;

use App\Models\EquipeTrabalho;
use App\Models\Tribo;
use Database\Seeders\Support\DemoRegistrationData;
use Illuminate\Database\Seeder;

class EquipeTrabalhoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Tribo::query()->count() < count(DemoRegistrationData::TRIBE_COLORS)) {
            $this->call(TriboSeeder::class);
        }

        $tribeIds = Tribo::query()
            ->orderBy('id')
            ->limit(count(DemoRegistrationData::TRIBE_COLORS))
            ->pluck('id')
            ->values();

        EquipeTrabalho::query()
            ->where('descricao', DemoRegistrationData::DEMO_OBSERVATION)
            ->delete();

        foreach (range(1, DemoRegistrationData::EQUIPE_TRABALHO_TOTAL) as $index) {
            EquipeTrabalho::unguarded(fn () => EquipeTrabalho::query()->create(
                DemoRegistrationData::equipeTrabalhoAttributes(
                    index: $index,
                    triboId: $tribeIds[($index - 1) % $tribeIds->count()],
                ),
            ));
        }

        DemoRegistrationData::ensureEquipeTrabalhoAvatarFiles();
    }
}
