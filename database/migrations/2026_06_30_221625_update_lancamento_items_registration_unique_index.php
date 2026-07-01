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
        Schema::table('lancamento_items', function (Blueprint $table): void {
            $table->index('lancamento_id', 'lancamento_items_lancamento_id_index');
        });

        Schema::table('lancamento_items', function (Blueprint $table): void {
            $table->dropUnique('lancamento_items_registration_unique');

            $table->unique([
                'lancamento_id',
                'registration_type',
                'registration_id',
                'categoria_lancamento_id',
            ], 'lancamento_items_registration_category_unique');
        });

        Schema::table('lancamento_items', function (Blueprint $table): void {
            $table->dropIndex('lancamento_items_lancamento_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lancamento_items', function (Blueprint $table): void {
            $table->unique([
                'lancamento_id',
                'registration_type',
                'registration_id',
            ], 'lancamento_items_registration_unique');
        });

        Schema::table('lancamento_items', function (Blueprint $table): void {
            $table->dropUnique('lancamento_items_registration_category_unique');
        });
    }
};
