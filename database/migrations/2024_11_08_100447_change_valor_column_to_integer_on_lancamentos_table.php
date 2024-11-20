<?php

use App\Models\Lancamento;
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
        $lancamentos = Lancamento::query()->get();
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->integer('valor')->change();
        });

        foreach ($lancamentos as $lancamento) {
            $lancamento->valor = $lancamento->valor * 100;
            $lancamento->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $lancamentos = Lancamento::query()->get();
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->float('valor')->change();
        });

        foreach ($lancamentos as $lancamento) {
            $lancamento->valor = $lancamento->valor / 100;
            $lancamento->save();
        }
    }
};
