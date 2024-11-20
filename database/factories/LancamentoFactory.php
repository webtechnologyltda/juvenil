<?php

namespace Database\Factories;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lancamento>
 */
class LancamentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'descricao' => $this->faker->sentence(),
            'comprador' => $this->faker->name(),
            'data' => $this->faker->dateTime(),
            'valor' => $this->faker->randomFloat(2, 0, 1000),
            'tipo' => $this->faker->randomElement([TipoLacamento::Receita->value,TipoLacamento::Doacao->value,TipoLacamento::Despesa->value]),
            'status' => $this->faker->randomElement([StatusLacamento::Pago->value,StatusLacamento::Cancelado->value,StatusLacamento::Pendente->value,]),
            'forma_pagamento' => $this->faker->randomElement([FormaPagamento::Dinheiro->value, FormaPagamento::Pix->value]),
            'comprovante' => $this->faker->imageUrl(640, 480),
            'user_id' => $this->faker->numberBetween(User::count(), 10),

        ];
    }
}
