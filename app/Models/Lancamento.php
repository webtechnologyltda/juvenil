<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lancamento extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'comprador',
        'data',
        'valor',
        'tipo',
        'categoria_lancamento_id',
        'status',
        'forma_pagamento',
        'comprovante',
        'user_id',
    ];

    protected $casts = [
        'tipo' => TipoLacamento::class,
        'status' => StatusLacamento::class,
        'forma_pagamento' => FormaPagamento::class,
        'comprovante' => 'array',
        'valor' => 'float'
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaLancamento::class, 'categoria_lancamento_id');
    }
}
