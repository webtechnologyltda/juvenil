<?php

namespace App\Models;

use App\Enums\TipoLacamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaLancamento extends Model
{
    use HasFactory;

    protected $table = 'categorias_lancamento';

    protected $fillable = [
        'nome',
        'tipo',
        'cor',
        'icone',
        'ativo',
    ];

    protected $casts = [
        'tipo' => TipoLacamento::class,
        'ativo' => 'boolean',
    ];

    public function lancamentos(): HasMany
    {
        return $this->hasMany(Lancamento::class, 'categoria_lancamento_id');
    }
}
