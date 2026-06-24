<?php

namespace Database\Seeders;

use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\TipoEquipeTrabalho;
use App\Models\EquipeTrabalho;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EquipeTrabalhoRosterSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::roster() as $team => $names) {
            $teamType = $team === 'Externa'
                ? TipoEquipeTrabalho::Externa
                : TipoEquipeTrabalho::Interna;

            foreach ($names as $name) {
                $registration = EquipeTrabalho::query()->firstOrCreate([
                    'nome' => $name,
                    'descricao' => $team,
                ], [
                    'data_form' => [],
                    'status' => StatusInscricaoEquipeTrabalho::Aprovado->value,
                    'tipo_equipe' => $teamType->value,
                ]);

                if ($registration->tipo_equipe !== $teamType) {
                    $registration->forceFill([
                        'tipo_equipe' => $teamType,
                    ])->save();
                }
            }
        }
    }

    public static function totalMembers(): int
    {
        return collect(self::roster())
            ->flatten(1)
            ->count();
    }

    public static function totalTeams(): int
    {
        return count(self::roster());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function roster(): array
    {
        return [
            'Coordenação' => [
                'Frei Kleiton',
                'Luana Cristina Fonseca',
                'Micael Márcio Borba',
                'Jocemar Silverio da Silva',
                'Anderson Santos de Oliveira',
            ],
            'Direção Espiritual' => [
                'Tiago Mellies',
                'Larissa Bissoli Vieira',
            ],
            'Servos' => [
                'Thaise Berkenbrock Inacio',
                'Diego André Inacio',
                'Ailton Motta',
                'Aldaberto Cidnei Menezes',
                'Ana Paula Amaro',
                'Antonio Braz de Oliveira',
                'Barbara Braz de Morais',
                'Bruna Thais Pinheiro Ferreira',
                'Bruno de Mello Sabino (Mega)',
                'Daiane Cristina Longo',
                'Dereck Adriano',
                'Edenilson de Souza',
                'Jefferson Macarini',
                'João Francisco Ferreiro',
                'Josilene Lessa Barbosa',
                'Katia Regina Brasil',
                'Laurinda Vilvert Inácio',
                'Lincon Kaue Espindola',
                'Marcelo Gomes da Silva',
                'Marcio Mello dos Santos',
                'Mareleia Nascimento Felicio',
                'Mariana Francisco Pacheco',
                'Marileia Moura Ferreira',
                'Mateus A. Grosskopf',
                'Osmair Donizete Noli (Zeca)',
                'Rafaela Elize Benassi',
                'Roberta Rebello',
                'Ronaldo Bertolli',
                'Saulo de Oliveira Silveira',
                'Tainara Mianes',
                'Tainara Rossatti de Souza',
                'Taise de Augustinho',
                'Tuanny Silva',
                'Valdemar Chagas Junior',
            ],
            'Cozinha' => [
                'Jean José Bento',
                'Francirlei Ribeiro',
                'Jair Figueredo',
                'José Maria de Oliveira',
                'Mariza Pivatto Ramos',
                'Mayara de Oliveira',
                'Rosiley Motta',
                'Schaieni Carolini Bento',
                'Sheila',
                'Valdeci Mafra',
            ],
            'Secretaria' => [
                'Leandro Marcos dos Santos',
                'Alexandra',
                'Aline Garcia Lazzaris',
                'Ana Cristina Figueredo',
                'Jocelaine Amaro Dittrich',
                'Renata Ricobom Pivatto',
                'Silvana Souza',
                'Solange Mello dos Santos',
            ],
            'Recreação' => [
                'Felipe Itamar da Silveira',
                'Diego dos Santos Souza',
            ],
            'Manutenção' => [
                'Ciduinei João da Silva',
                'Antonio Fernandes (Chico)',
                'Ataídes dos Santos',
                'Everton Gazaniga',
                'Iago Gonçalves Borba',
                'Jairo Amaro',
                'Jonatan C. de Souza',
                'Pedro Dorval Felicio',
            ],
            'Intercessão' => [
                'Nerozilda Ferreira',
                'Alcione Santos Garcez',
                'Dileta de Menezes',
                'Edmilson Miguel Holtin',
                'Luciana de Souza',
                'Monica Zimmermann',
                'Nilsa Rodrigues',
                'Rozane Fatima Santos de Oliveira',
                'Salete Ribeiro da Silva',
                'Zeferino Ferreira',
                'Zulmira Fideleski',
            ],
            'Apoio' => [
                'Camila Thaisi Inácio da Cunha',
                'Joabe da Silva Maria',
                'Kaylayne Eduarda de Almeida Gazaniga',
                'Milton Bortolado',
                'Tieli Costa de Oliveira',
            ],
            'Ordem e Limpeza' => [
                'Magali de Fátima',
                'Moacir da Silva Filho',
                'Ane Karoline Portella',
                'Jennifer Laura Rocha',
                'José Augusto Gonçalves',
                'Mirian Carmen de Mello',
                'Pedro Ferreira de Oliveira',
                'Silvia Silva',
                'Tatiane de Souza',
            ],
            'Enfermagem' => [
                'Sabrina Coelho Bento',
            ],
            'Animação' => [
                'Rodrigo Berlanda Pimental',
                'Cleiton',
                'Hermínio',
                'Lucas',
                'Marcos',
                'Nei',
            ],
            'Externa' => [
                'Eduana Fonseca White',
                'Geovana Floriano Sabino',
                'Alexsandro do Nascimento',
                'Ane Caroline Adriano',
                'Arianne dos Santos Vanderlinde',
                'Berg',
                'Camila Aparecida Pinheiro Marmitt',
                'Carlos Antonio Gazaniga',
                'Cris',
                'Cristiane Espíndola',
                'Daiane Russi da Silva Bastos',
                'Edneia Francieli Rodrigues de Almeida Gazaniga',
                'Franciane Marquetti',
                'Jaison Amaro',
                'Juliana',
                'Mariane de Freitas Maria',
                'Naiara Dognini',
                'Naiara Gabriela Assis da Silva',
                'Rodrigo da Silva',
                'Sidnei de Lima',
            ],
            'Missão' => [
                'Graziele de Oliveira Andriani',
                'Luiz Fernando Andriani Júnior',
                'Adriana de Souza dos Santos',
                'Amanda Goulart Pontes',
                'Anderson Rodrigues',
                'Andrea Rosemari de Souza dos Santos',
                'Augustinho Pedrozo',
                'Benta keller',
                'Bianca Reiser',
                'Daniela longo',
                'Dionei Pereira',
                'Elisângela Alexandre dos Santos',
                'Fabiane Pierre dos Passos',
                'Fabio Mello dos Santos',
                'Felipe Mellies',
                'Gabriella Estrogueia Mellies',
                'Giancarlo Mafra',
                'Gilmar Martins',
                'Ivan Jayme de Souza',
                'Jéssica Felício de Souza Nunes',
                'João Guilherme Corrêa Harger',
                'João Paulo Nunes',
                'Luciana do Rocio Ribeiro Oliveira',
                'Luciano dos Santos',
                'Luiz Henrique de Mello',
                'Márcia Aparecida Silva Ricardo',
                'Maria Eva Amaro',
                'Mariela Marcelino de Mello',
                'Priscila Mayara Kienolt',
                'Renato Nunes da Silva',
                'Roberta Ricardo de Souza',
                'Rosinei Aparecida Oliveira',
                'Sandra Catarina Inácio',
                'Simone M Henrique de Souza',
                'Solange Gonçalves Alves',
                'Terezinha Oliveira',
                'Tiago Etges',
                'Vanessa Schmitter',
                'Zoraide da Silva',
            ],
        ];
    }
}
