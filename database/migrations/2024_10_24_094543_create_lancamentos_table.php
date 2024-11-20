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
        Schema::create('lancamentos', function (Blueprint $table) {

            $table->id();
            $table->string('nome');
            $table->string('descricao')->nullable();
            $table->string('comprador')->nullable();
            $table->datetime('data');
            $table->float('valor', 3);
            $table->integer('tipo');
            $table->integer('status');
            $table->integer('forma_pagamento');
            $table->string( 'comprovante');
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lancamentos');
    }
};
