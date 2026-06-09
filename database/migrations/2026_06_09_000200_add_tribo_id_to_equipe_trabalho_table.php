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
        Schema::table('equipe_trabalho', function (Blueprint $table): void {
            $table->foreignId('tribo_id')
                ->nullable()
                ->after('status')
                ->constrained('tribos')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipe_trabalho', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('tribo_id');
        });
    }
};
