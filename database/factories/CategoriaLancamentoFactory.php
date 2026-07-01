<?php

namespace Database\Factories;

use App\Enums\TipoLacamento;
use App\Models\CategoriaLancamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaLancamento>
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
            'valor_padrao' => 0,
            'cor' => $this->faker->hexColor(),
            'icone' => 'heroicon-o-tag',
            'ativo' => true,
        ];
    }
}
