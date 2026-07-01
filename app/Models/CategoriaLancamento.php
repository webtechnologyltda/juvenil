<?php

namespace App\Models;

use App\Enums\TipoLacamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaLancamento extends Model
{
    use HasFactory;

    public const SYSTEM_CATEGORY_INSCRICAO = 'inscricao';

    public const SYSTEM_CATEGORY_CONTRIBUICAO_EQUIPE_TRABALHO = 'contribuicao_equipe_trabalho';

    protected $table = 'categorias_lancamento';

    protected $fillable = [
        'system_key',
        'nome',
        'tipo',
        'valor_padrao',
        'cor',
        'icone',
        'ativo',
    ];

    protected $casts = [
        'tipo' => TipoLacamento::class,
        'valor_padrao' => 'integer',
        'ativo' => 'boolean',
    ];

    public static function booted(): void
    {
        static::saving(function (CategoriaLancamento $categoriaLancamento): void {
            if (! $categoriaLancamento->exists || ! $categoriaLancamento->isSystemDefault()) {
                return;
            }

            foreach (['system_key', 'nome', 'tipo', 'valor_padrao', 'ativo'] as $attribute) {
                if ($categoriaLancamento->isDirty($attribute)) {
                    $categoriaLancamento->setAttribute($attribute, $categoriaLancamento->getOriginal($attribute));
                }
            }
        });

        static::deleting(fn (CategoriaLancamento $categoriaLancamento): bool => ! $categoriaLancamento->isSystemDefault());
    }

    public function items(): HasMany
    {
        return $this->hasMany(LancamentoItem::class, 'categoria_lancamento_id');
    }

    public function lancamentos(): BelongsToMany
    {
        return $this->belongsToMany(
            Lancamento::class,
            'lancamento_items',
            'categoria_lancamento_id',
            'lancamento_id',
        )->distinct();
    }

    public function isSystemDefault(): bool
    {
        return in_array($this->system_key, [
            self::SYSTEM_CATEGORY_INSCRICAO,
            self::SYSTEM_CATEGORY_CONTRIBUICAO_EQUIPE_TRABALHO,
        ], true);
    }

    /**
     * @return array<string, array{nome: string, tipo: TipoLacamento, cor: string, icone: string}>
     */
    public static function systemDefaults(): array
    {
        return [
            self::SYSTEM_CATEGORY_INSCRICAO => [
                'nome' => 'Inscrição',
                'tipo' => TipoLacamento::Receita,
                'cor' => '#f46b12',
                'icone' => 'heroicon-o-ticket',
            ],
            self::SYSTEM_CATEGORY_CONTRIBUICAO_EQUIPE_TRABALHO => [
                'nome' => 'Contribuição Equipe de Trabalho',
                'tipo' => TipoLacamento::Receita,
                'cor' => '#0ea5e9',
                'icone' => 'heroicon-o-identification',
            ],
        ];
    }

    public static function ensureSystemDefaults(): void
    {
        foreach (self::systemDefaults() as $systemKey => $attributes) {
            $category = self::query()
                ->where('system_key', $systemKey)
                ->orWhere(function ($query) use ($attributes): void {
                    $query
                        ->where('nome', $attributes['nome'])
                        ->where('tipo', $attributes['tipo']->value);
                })
                ->first();

            if ($category) {
                self::query()
                    ->whereKey($category->id)
                    ->update([
                        'system_key' => $systemKey,
                        'nome' => $attributes['nome'],
                        'tipo' => $attributes['tipo']->value,
                        'ativo' => true,
                    ]);

                continue;
            }

            self::query()->create([
                'system_key' => $systemKey,
                'nome' => $attributes['nome'],
                'tipo' => $attributes['tipo'],
                'cor' => $attributes['cor'],
                'icone' => $attributes['icone'],
                'ativo' => true,
            ]);
        }
    }
}
