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
        Schema::table('categorias_lancamento', function (Blueprint $table) {
            $table
                ->unsignedInteger('valor_padrao')
                ->default(0)
                ->after('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categorias_lancamento', function (Blueprint $table) {
            $table->dropColumn('valor_padrao');
        });
    }
};
