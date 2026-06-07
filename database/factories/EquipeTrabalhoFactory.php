<?php

namespace Database\Factories;

use App\Models\EquipeTrabalho;
use Database\Seeders\Support\DemoRegistrationData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipeTrabalho>
 */
class EquipeTrabalhoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return DemoRegistrationData::equipeTrabalhoAttributes(1);
    }
}
