<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
