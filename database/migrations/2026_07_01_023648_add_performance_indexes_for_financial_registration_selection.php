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
        Schema::table('campistas', function (Blueprint $table): void {
            $table->index(['status', 'nome', 'id'], 'campistas_status_nome_lookup');
        });

        Schema::table('equipe_trabalho', function (Blueprint $table): void {
            $table->index(['status', 'nome', 'id'], 'equipe_trabalho_status_nome_lookup');
            $table->index(['tipo_equipe', 'status', 'id'], 'equipe_trabalho_tipo_status_lookup');
        });

        Schema::table('lancamentos', function (Blueprint $table): void {
            $table->index(['status', 'id'], 'lancamentos_status_id_lookup');
        });

        Schema::table('lancamento_items', function (Blueprint $table): void {
            $table->index(
                ['registration_type', 'registration_id', 'categoria_lancamento_id', 'lancamento_id'],
                'lancamento_items_registration_payment_lookup',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lancamento_items', function (Blueprint $table): void {
            $table->dropIndex('lancamento_items_registration_payment_lookup');
        });

        Schema::table('lancamentos', function (Blueprint $table): void {
            $table->dropIndex('lancamentos_status_id_lookup');
        });

        Schema::table('equipe_trabalho', function (Blueprint $table): void {
            $table->dropIndex('equipe_trabalho_tipo_status_lookup');
            $table->dropIndex('equipe_trabalho_status_nome_lookup');
        });

        Schema::table('campistas', function (Blueprint $table): void {
            $table->dropIndex('campistas_status_nome_lookup');
        });
    }
};
