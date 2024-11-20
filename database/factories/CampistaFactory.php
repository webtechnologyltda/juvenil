<?php

namespace Database\Factories;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Models\Tribo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
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
        return [
            'nome' => $this->faker->name(),
            'avatar_url' => $this->faker->imageUrl(),

            'form_data' => json_decode('{
                                               "rua":"anita moraes",
                                               "peso":"70",
                                               "sexo":"M",
                                               "altura":"1.70",
                                               "bairro":"centro",
                                               "cidade":"navegantes",
                                               "estado":"sc",
                                               "numero":"152",
                                               "remedio":"dipirona",
                                               "nome_mae":"Maria",
                                               "nome_pai":"Joao",
                                               "rede_social":"@teste",
                                               "recomendacao":"vestir camiseta no avesso",
                                               "algum_parente":"1",
                                               "data_nacimento":"15/02/2000",
                                               "ponto_referencia":"CAIC",
                                               "tamanho_camiseta":"O",
                                               "telefone_campista":"(47) 9 9999-8956",
                                               "telefone_reponsavel":"(47) 9 9999-8956",
                                               "ja_participou_retiro":"1",
                                               "retiro_que_participou":[
                                                  "Teste"
                                               ],
                                               "tamanho_camiseta_outro":"XGG",
                                               "algum_parente_participante":[
                                                  "Teste"
                                               ]
                                            }', true),
            'status' => $this->faker->randomElement([StatusInscricao::Pendente->value,
                StatusInscricao::Pago->value,
                StatusInscricao::Cancelado->value]),
            'dia_pagamento' => Carbon::now()->format('Y-m-d'),
            'forma_pagamento' => $this->faker->randomElement([FormaPagamento::Dinheiro->value, FormaPagamento::Pix->value]),
            'observacoes' => 'Criado via Seeder',
            'presenca' => $this->faker->boolean(),
            'tribo_id' => $this->faker->numberBetween(Tribo::count(), 10),
            'user_id' => $this->faker->numberBetween(User::count(), 10),

        ];
    }
}
