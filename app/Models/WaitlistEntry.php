<?php

namespace App\Models;

use App\Enums\WaitlistEntryStatus;
use Database\Factories\WaitlistEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitlistEntry extends Model
{
    /** @use HasFactory<WaitlistEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'nome',
        'telefone',
        'telefone_normalizado',
        'email',
        'sexo',
        'data_nascimento',
        'observacao',
        'status',
        'accepted_privacy_at',
        'admin_notes',
        'invitation_token_hash',
        'invitation_token_encrypted',
        'invitation_generated_at',
        'invitation_generated_by',
        'invitation_expires_at',
        'invitation_accepted_at',
        'cancelled_at',
        'cancelled_by',
        'campista_id',
    ];

    protected $attributes = [
        'status' => WaitlistEntryStatus::Aguardando->value,
    ];

    protected $hidden = [
        'invitation_token_hash',
        'invitation_token_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'status' => WaitlistEntryStatus::class,
            'data_nascimento' => 'date',
            'accepted_privacy_at' => 'datetime',
            'invitation_generated_at' => 'datetime',
            'invitation_expires_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function campista(): BelongsTo
    {
        return $this->belongsTo(Campista::class);
    }

    public function invitationGeneratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitation_generated_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
