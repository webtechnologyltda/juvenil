<?php

namespace Database\Factories;

use App\Enums\WaitlistEntryStatus;
use App\Models\WaitlistEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaitlistEntry>
 */
class WaitlistEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'telefone' => '(47) 9 '.fake()->numerify('####-####'),
            'telefone_normalizado' => '55479'.fake()->numerify('########'),
            'email' => fake()->safeEmail(),
            'sexo' => fake()->randomElement(['M', 'F']),
            'data_nascimento' => fake()->dateTimeBetween('-18 years', '-12 years')->format('Y-m-d'),
            'observacao' => null,
            'status' => WaitlistEntryStatus::Aguardando,
            'accepted_privacy_at' => now(),
        ];
    }
}
