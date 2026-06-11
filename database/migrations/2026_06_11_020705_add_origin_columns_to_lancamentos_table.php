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
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->string('origin', 50)->nullable()->after('user_id');
            $table->string('origin_context', 50)->nullable()->after('origin');
            $table->index(['origin', 'origin_context']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table) {
            $table->dropIndex(['origin', 'origin_context']);
            $table->dropColumn(['origin', 'origin_context']);
        });
    }
};
