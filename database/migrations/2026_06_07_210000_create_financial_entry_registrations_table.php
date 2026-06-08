<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_entry_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lancamento_id')
                ->constrained('lancamentos')
                ->cascadeOnDelete();
            $table->string('registration_type');
            $table->unsignedBigInteger('registration_id');
            $table->unsignedInteger('amount');
            $table->timestamps();

            $table->index([
                'registration_type',
                'registration_id',
            ], 'fer_registration_index');
            $table->unique([
                'lancamento_id',
                'registration_type',
                'registration_id',
            ], 'financial_entry_registration_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_entry_registrations');
    }
};
