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
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_type', 64);
            $table->string('status', 32)->default('pending');
            $table->string('filters_hash', 64);
            $table->json('filters');
            $table->string('disk', 64)->default('local');
            $table->string('file_path')->nullable();
            $table->string('filename');
            $table->text('error_message')->nullable();
            $table->unsignedInteger('progress_current')->default(0);
            $table->unsignedInteger('progress_total')->default(100);
            $table->string('progress_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['user_id', 'report_type', 'filters_hash'],
                'report_exports_unique_lookup',
            );
            $table->index(
                ['user_id', 'status', 'expires_at'],
                'report_exports_status_lookup',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
