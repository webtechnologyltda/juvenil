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
        Schema::create('waitlist_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('telefone', 32);
            $table->string('telefone_normalizado', 32)->nullable();
            $table->string('email')->nullable();
            $table->char('sexo', 1);
            $table->date('data_nascimento')->nullable();
            $table->text('observacao')->nullable();
            $table->string('status', 24)->default('aguardando');
            $table->timestamp('accepted_privacy_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('invitation_token_hash', 64)->nullable()->unique();
            $table->text('invitation_token_encrypted')->nullable();
            $table->timestamp('invitation_generated_at')->nullable();
            $table->foreignId('invitation_generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('campista_id')
                ->nullable()
                ->constrained('campistas')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'sexo', 'created_at']);
            $table->index(['sexo', 'created_at']);
            $table->index('telefone_normalizado');
            $table->index('invitation_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waitlist_entries');
    }
};
