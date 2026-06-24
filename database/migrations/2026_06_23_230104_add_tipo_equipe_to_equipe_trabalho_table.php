<?php

use App\Enums\TipoEquipeTrabalho;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('equipe_trabalho', function (Blueprint $table): void {
            $table->unsignedTinyInteger('tipo_equipe')
                ->default(TipoEquipeTrabalho::Interna->value)
                ->after('descricao');
        });

        DB::table('equipe_trabalho')
            ->where('descricao', 'Externa')
            ->update(['tipo_equipe' => TipoEquipeTrabalho::Externa->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipe_trabalho', function (Blueprint $table): void {
            $table->dropColumn('tipo_equipe');
        });
    }
};
