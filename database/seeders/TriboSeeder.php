<?php

namespace Database\Seeders;

use App\Models\Tribo;
use App\Support\Tribes\TribeColor;
use Database\Seeders\Support\DemoRegistrationData;
use Illuminate\Database\Seeder;

class TriboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (DemoRegistrationData::TRIBE_COLORS as $index => $color) {
            Tribo::query()->updateOrCreate(
                ['id' => $index + 1],
                [
                    'cor' => $color,
                    'cor_hex' => TribeColor::fromName($color),
                ],
            );
        }
    }
}
