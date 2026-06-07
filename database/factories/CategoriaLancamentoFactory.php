<?php

namespace Database\Factories;

use App\Enums\TipoLacamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategoriaLancamento>
 */
class CategoriaLancamentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => $this->faker->unique()->words(2, true),
            'tipo' => $this->faker->randomElement([
                TipoLacamento::Receita->value,
                TipoLacamento::Despesa->value,
                TipoLacamento::Doacao->value,
            ]),
            'cor' => $this->faker->hexColor(),
            'icone' => 'heroicon-o-tag',
            'ativo' => true,
        ];
    }
}
