<?php

namespace App\Models;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'valor' => 'integer',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaLancamento::class, 'categoria_lancamento_id');
    }

    public function registrationPayments(): HasMany
    {
        return $this->hasMany(FinancialEntryRegistration::class, 'lancamento_id');
    }

    public function getRegistrationPaymentsSummaryAttribute(): string
    {
        $payments = $this->relationLoaded('registrationPayments')
            ? $this->registrationPayments
            : $this->registrationPayments()->with('registration')->get();

        if ($payments->isEmpty()) {
            return 'Sem inscrições vinculadas';
        }

        return $payments
            ->map(function (FinancialEntryRegistration $payment): string {
                $registration = $payment->registration;
                $type = $registration instanceof Campista ? 'Campista' : 'Equipe';
                $name = (string) ($registration?->getAttribute('nome') ?? 'Inscrição removida');

                return sprintf(
                    '%s #%s - %s (%s)',
                    $type,
                    $payment->registration_id,
                    $name,
                    'R$ '.number_format($payment->amount / 100, 2, ',', '.'),
                );
            })
            ->implode("\n");
    }
}
