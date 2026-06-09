<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tribos', function (Blueprint $table): void {
            $table->string('cor_hex', 7)->nullable()->after('cor');
        });

        DB::table('tribos')
            ->select(['id', 'cor'])
            ->orderBy('id')
            ->get()
            ->each(function (object $tribe): void {
                DB::table('tribos')
                    ->where('id', $tribe->id)
                    ->update(['cor_hex' => $this->colorFor($tribe->cor)]);
            });
    }

    public function down(): void
    {
        Schema::table('tribos', function (Blueprint $table): void {
            $table->dropColumn('cor_hex');
        });
    }

    private function colorFor(?string $name): string
    {
        $normalized = Str::lower(Str::ascii(trim((string) $name)));

        if (preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $normalized) === 1) {
            return $normalized;
        }

        return match ($normalized) {
            'azul' => '#2563eb',
            'vermelha', 'vermelho' => '#dc2626',
            'verde' => '#16a34a',
            'amarela', 'amarelo' => '#eab308',
            'roxa', 'roxo' => '#7c3aed',
            'laranja' => '#f97316',
            'rosa' => '#ec4899',
            'branca', 'branco' => '#f8fafc',
            'preta', 'preto' => '#111827',
            'cinza' => '#64748b',
            default => '#94a3b8',
        };
    }
};
