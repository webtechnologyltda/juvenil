<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LancamentoItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
        'valor',
        'categoria_lancamento_id',
        'registration_type',
        'registration_id',
    ];

    protected $casts = [
        'valor' => 'integer',
    ];

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaLancamento::class, 'categoria_lancamento_id');
    }

    public function registration(): MorphTo
    {
        return $this->morphTo();
    }
}
