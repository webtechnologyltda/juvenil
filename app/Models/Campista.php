<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Campista extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'nome',
        'avatar_url',
        'form_data',
        'status',
        'observacoes',
        'forma_pagamento',
        'dia_pagamento',
        'presenca',
        'tribo_id',
    ];

    protected $casts = [
        'form_data' => 'array',
        'dia_pagamento' => 'datetime',
        'avatar_url' => 'string',
        'forma_pagamento' => FormaPagamento::class,
        'status' => StatusInscricao::class,
        'presenca' => 'boolean',

    ];

    public function tribo()
    {
        return $this->belongsTo(Tribo::class);
    }
}
