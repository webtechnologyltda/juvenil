<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'batch_code',
        'user_id',
    ];

    protected $casts = [
        'tipo' => TipoLacamento::class,
        'status' => StatusLacamento::class,
        'forma_pagamento' => FormaPagamento::class,
        'comprovante' => 'array',
        'valor' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(LancamentoItem::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            CategoriaLancamento::class,
            'lancamento_items',
            'lancamento_id',
            'categoria_lancamento_id',
        )->distinct();
    }

    public function getRegistrationPaymentsSummaryAttribute(): string
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('registration')->get();

        $registrationItems = $items->filter(fn (LancamentoItem $item): bool => filled($item->registration_type) && filled($item->registration_id));

        if ($registrationItems->isEmpty()) {
            return 'Sem inscrições vinculadas';
        }

        return $registrationItems
            ->map(function (LancamentoItem $item): string {
                $registration = $item->registration;
                $type = $registration instanceof Campista ? 'Campista' : 'Equipe';
                $name = (string) ($registration?->getAttribute('nome') ?? 'Inscrição removida');

                return sprintf(
                    '%s #%s - %s (%s)',
                    $type,
                    $item->registration_id,
                    $name,
                    'R$ '.number_format($item->valor / 100, 2, ',', '.'),
                );
            })
            ->implode("\n");
    }

    public function getCategoriesSummaryAttribute(): string
    {
        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with('categoria')->get();

        $categories = $items
            ->map(fn (LancamentoItem $item): ?string => $item->categoria?->nome)
            ->filter()
            ->unique()
            ->values();

        return $categories->isEmpty()
            ? 'Sem categoria'
            : $categories->implode(', ');
    }
}
