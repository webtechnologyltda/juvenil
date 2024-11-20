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
        Schema::create('campistas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('avatar_url')->nullable();
            $table->json('form_data')->nullable();
            $table->integer('status')->default(0);
            $table->foreignId('user_id')->nullable()->constrained();
            $table->integer('forma_pagamento')->nullable();
            $table->timestamp('dia_pagamento')->nullable();
            $table->text('observacoes')->nullable();
            $table->boolean('presenca')->default(false);
            $table->foreignId( 'tribo_id')->nullable()->constrained('tribos');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campistas');
    }
};
