<?php

namespace Database\Factories;

use App\Models\Model;
use Database\Seeders\Support\DemoRegistrationData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class CampistaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return DemoRegistrationData::campistaAttributes(1);
    }
}
