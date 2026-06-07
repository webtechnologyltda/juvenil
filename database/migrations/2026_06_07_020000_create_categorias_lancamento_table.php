<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_lancamento', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->integer('tipo');
            $table->string('cor', 7)->default('#f46b12');
            $table->string('icone')->default('heroicon-o-tag');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['nome', 'tipo']);
        });

        Schema::table('lancamentos', function (Blueprint $table) {
            $table->foreignId('categoria_lancamento_id')
                ->nullable()
                ->constrained('categorias_lancamento')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_lancamento_id');
        });

        Schema::dropIfExists('categorias_lancamento');
    }
};
