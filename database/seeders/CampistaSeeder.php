<?php

namespace Database\Seeders;

use App\Models\Campista;
use App\Models\Tribo;
use Database\Seeders\Support\DemoRegistrationData;
use Illuminate\Database\Seeder;

class CampistaSeeder extends Seeder
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

        Campista::query()
            ->whereIn('observacoes', [
                DemoRegistrationData::DEMO_OBSERVATION,
                'Criado via Seeder',
            ])
            ->delete();

        foreach (range(1, DemoRegistrationData::CAMPISTA_TOTAL) as $index) {
            Campista::query()->create(DemoRegistrationData::campistaAttributes(
                index: $index,
                triboId: $tribeIds[($index - 1) % $tribeIds->count()],
            ));
        }
    }
}
