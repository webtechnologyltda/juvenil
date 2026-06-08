<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SYSTEM_CATEGORIES = [
        'inscricao' => [
            'nome' => 'Inscrição',
            'tipo' => 0,
            'cor' => '#f46b12',
            'icone' => 'heroicon-o-ticket',
        ],
        'contribuicao_equipe_trabalho' => [
            'nome' => 'Contribuição Equipe de Trabalho',
            'tipo' => 0,
            'cor' => '#0ea5e9',
            'icone' => 'heroicon-o-identification',
        ],
    ];

    public function up(): void
    {
        Schema::table('categorias_lancamento', function (Blueprint $table) {
            $table->string('system_key')->nullable()->after('id');
            $table->unique('system_key');
        });

        foreach (self::SYSTEM_CATEGORIES as $systemKey => $category) {
            $this->ensureSystemCategory($systemKey, $category);
        }
    }

    public function down(): void
    {
        DB::table('categorias_lancamento')
            ->whereIn('system_key', array_keys(self::SYSTEM_CATEGORIES))
            ->update(['system_key' => null]);

        Schema::table('categorias_lancamento', function (Blueprint $table) {
            $table->dropUnique('categorias_lancamento_system_key_unique');
            $table->dropColumn('system_key');
        });
    }

    /**
     * @param  array{nome: string, tipo: int, cor: string, icone: string}  $category
     */
    private function ensureSystemCategory(string $systemKey, array $category): void
    {
        $now = now();

        $existingId = DB::table('categorias_lancamento')
            ->where('system_key', $systemKey)
            ->orWhere(function ($query) use ($category): void {
                $query
                    ->where('nome', $category['nome'])
                    ->where('tipo', $category['tipo']);
            })
            ->value('id');

        if ($existingId) {
            DB::table('categorias_lancamento')
                ->where('id', $existingId)
                ->update([
                    'system_key' => $systemKey,
                    'nome' => $category['nome'],
                    'tipo' => $category['tipo'],
                    'ativo' => true,
                    'updated_at' => $now,
                ]);

            return;
        }

        DB::table('categorias_lancamento')->insert([
            'system_key' => $systemKey,
            'nome' => $category['nome'],
            'tipo' => $category['tipo'],
            'cor' => $category['cor'],
            'icone' => $category['icone'],
            'ativo' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
