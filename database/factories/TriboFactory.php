<?php

namespace Database\Factories;

use App\Models\Tribo;
use App\Support\Tribes\TribeColor;
use Database\Seeders\Support\DemoRegistrationData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tribo>
 */
class TriboFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cor' => DemoRegistrationData::TRIBE_COLORS[0],
            'cor_hex' => TribeColor::fromName(DemoRegistrationData::TRIBE_COLORS[0]),
        ];
    }
}
