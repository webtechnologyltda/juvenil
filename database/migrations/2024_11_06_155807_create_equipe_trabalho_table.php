<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipe_trabalho', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('avatar_url')->nullable();
            $table->json('data_form');
            $table->integer('status')->default(\App\Enums\StatusInscricaoEquipeTrabalho::Pendente->value);
            $table->string('descricao')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipe_trabalho');
    }
};
