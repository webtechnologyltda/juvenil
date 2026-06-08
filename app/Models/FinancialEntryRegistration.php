<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialEntryRegistration extends Model
{
    protected $fillable = [
        'lancamento_id',
        'registration_type',
        'registration_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    public function lancamento(): BelongsTo
    {
        return $this->belongsTo(Lancamento::class, 'lancamento_id');
    }

    public function registration(): MorphTo
    {
        return $this->morphTo();
    }
}
