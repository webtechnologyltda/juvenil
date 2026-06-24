<?php

namespace App\Models;

use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\TipoEquipeTrabalho;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use OwenIt\Auditing\Contracts\Auditable;

class EquipeTrabalho extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'equipe_trabalho';

    protected $fillable = [
        'nome',
        'avatar_url',
        'data_form',
        'status',
        'tribo_id',
        'descricao',
        'tipo_equipe',
    ];

    protected $attributes = [
        'data_form' => '[]',
        'tipo_equipe' => TipoEquipeTrabalho::Interna->value,
    ];

    protected $casts = [
        'data_form' => 'array',
        'avatar_url' => 'string',
        'status' => StatusInscricaoEquipeTrabalho::class,
        'tipo_equipe' => TipoEquipeTrabalho::class,
    ];

    public function lancamentoItems(): MorphMany
    {
        return $this->morphMany(LancamentoItem::class, 'registration');
    }

    public function tribo()
    {
        return $this->belongsTo(Tribo::class);
    }
}
