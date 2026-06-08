<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lancamentos', function (Blueprint $table): void {
            $table->string('batch_code', 20)
                ->nullable()
                ->after('comprovante')
                ->index();
        });

        Schema::create('lancamento_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lancamento_id')
                ->constrained('lancamentos')
                ->cascadeOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->unsignedInteger('valor');
            $table->foreignId('categoria_lancamento_id')
                ->constrained('categorias_lancamento')
                ->restrictOnDelete();
            $table->string('registration_type')->nullable();
            $table->unsignedBigInteger('registration_id')->nullable();
            $table->timestamps();

            $table->index([
                'registration_type',
                'registration_id',
            ], 'lancamento_items_registration_index');
            $table->unique([
                'lancamento_id',
                'registration_type',
                'registration_id',
            ], 'lancamento_items_registration_unique');
        });

        $this->migrateExistingRowsToItems();

        Schema::dropIfExists('financial_entry_registrations');

        if (Schema::hasColumn('lancamentos', 'categoria_lancamento_id')) {
            Schema::table('lancamentos', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('categoria_lancamento_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('lancamentos', function (Blueprint $table): void {
            $table->foreignId('categoria_lancamento_id')
                ->nullable()
                ->constrained('categorias_lancamento')
                ->restrictOnDelete();
        });

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

        $now = now();

        DB::table('lancamentos')
            ->orderBy('id')
            ->each(function (object $lancamento) use ($now): void {
                $firstItem = DB::table('lancamento_items')
                    ->where('lancamento_id', $lancamento->id)
                    ->orderBy('id')
                    ->first();

                if ($firstItem !== null) {
                    DB::table('lancamentos')
                        ->where('id', $lancamento->id)
                        ->update(['categoria_lancamento_id' => $firstItem->categoria_lancamento_id]);
                }

                DB::table('lancamento_items')
                    ->where('lancamento_id', $lancamento->id)
                    ->whereNotNull('registration_type')
                    ->whereNotNull('registration_id')
                    ->orderBy('id')
                    ->each(function (object $item) use ($now): void {
                        DB::table('financial_entry_registrations')->insert([
                            'lancamento_id' => $item->lancamento_id,
                            'registration_type' => $item->registration_type,
                            'registration_id' => $item->registration_id,
                            'amount' => $item->valor,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    });
            });

        Schema::dropIfExists('lancamento_items');

        Schema::table('lancamentos', function (Blueprint $table): void {
            $table->dropColumn('batch_code');
        });
    }

    private function migrateExistingRowsToItems(): void
    {
        $now = now();
        $hasFinancialLinks = Schema::hasTable('financial_entry_registrations');

        DB::table('lancamentos')
            ->orderBy('id')
            ->each(function (object $lancamento) use ($hasFinancialLinks, $now): void {
                $categoryId = $lancamento->categoria_lancamento_id ?? $this->fallbackCategoryId((int) $lancamento->tipo);

                if ($categoryId === null) {
                    return;
                }

                $total = abs((int) $lancamento->valor);
                $allocated = 0;

                $links = $hasFinancialLinks
                    ? DB::table('financial_entry_registrations')
                        ->where('lancamento_id', $lancamento->id)
                        ->orderBy('id')
                        ->get()
                    : collect();

                foreach ($links as $link) {
                    $amount = abs((int) $link->amount);
                    $allocated += $amount;

                    DB::table('lancamento_items')->insert([
                        'lancamento_id' => $lancamento->id,
                        'nome' => $this->registrationName($link->registration_type, (int) $link->registration_id),
                        'descricao' => null,
                        'valor' => $amount,
                        'categoria_lancamento_id' => $categoryId,
                        'registration_type' => $link->registration_type,
                        'registration_id' => $link->registration_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $residual = $total - $allocated;

                if ($links->isEmpty()) {
                    DB::table('lancamento_items')->insert([
                        'lancamento_id' => $lancamento->id,
                        'nome' => (string) $lancamento->nome,
                        'descricao' => $lancamento->descricao,
                        'valor' => $total,
                        'categoria_lancamento_id' => $categoryId,
                        'registration_type' => null,
                        'registration_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    return;
                }

                if ($residual > 0) {
                    DB::table('lancamento_items')->insert([
                        'lancamento_id' => $lancamento->id,
                        'nome' => 'Saldo não vinculado',
                        'descricao' => $lancamento->descricao,
                        'valor' => $residual,
                        'categoria_lancamento_id' => $categoryId,
                        'registration_type' => null,
                        'registration_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    private function registrationName(?string $registrationType, int $registrationId): string
    {
        $table = match ($registrationType) {
            App\Models\Campista::class => 'campistas',
            App\Models\EquipeTrabalho::class => 'equipe_trabalho',
            default => null,
        };

        if ($table === null) {
            return 'Inscrição removida';
        }

        return (string) (DB::table($table)->where('id', $registrationId)->value('nome') ?? 'Inscrição removida');
    }

    private function fallbackCategoryId(int $type): ?int
    {
        return DB::table('categorias_lancamento')
            ->where('tipo', $type)
            ->orderBy('id')
            ->value('id');
    }
};
