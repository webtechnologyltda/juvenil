<?php

namespace App\Support\Campistas;

use App\Enums\StatusInscricao;
use App\Enums\WaitlistEntryStatus;
use App\Models\Campista;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Settings\GeneralSettings;
use App\Support\AtendenteWhatsapp;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WaitlistManager
{
    /**
     * @return array<int, string>
     */
    public function activeStatuses(): array
    {
        return [
            WaitlistEntryStatus::Aguardando->value,
            WaitlistEntryStatus::Convocado->value,
        ];
    }

    public function normalizePhone(?string $phone): ?string
    {
        return AtendenteWhatsapp::number($phone);
    }

    public function activeDemandForSex(string $sex): int
    {
        return $this->activeQueueQuery()
            ->where('sexo', $sex)
            ->count();
    }

    public function activeDemand(): int
    {
        return $this->activeQueueQuery()->count();
    }

    public function activeReservationsForSex(string $sex, ?WaitlistEntry $excluding = null): int
    {
        return $this->activeReservationQuery()
            ->where('sexo', $sex)
            ->when($excluding !== null, fn ($query) => $query->whereKeyNot($excluding->getKey()))
            ->count();
    }

    public function activeReservations(?WaitlistEntry $excluding = null): int
    {
        return $this->activeReservationQuery()
            ->when($excluding !== null, fn ($query) => $query->whereKeyNot($excluding->getKey()))
            ->count();
    }

    public function generalPosition(WaitlistEntry $entry): ?int
    {
        if (! $this->isActiveQueueEntry($entry)) {
            return null;
        }

        return $this->activeQueueQuery()
            ->where(function ($query) use ($entry): void {
                $query
                    ->where('created_at', '<', $entry->created_at)
                    ->orWhere(function ($query) use ($entry): void {
                        $query
                            ->where('created_at', $entry->created_at)
                            ->where('id', '<=', $entry->id);
                    });
            })
            ->count();
    }

    public function sexPosition(WaitlistEntry $entry): ?int
    {
        if (! $this->isActiveQueueEntry($entry)) {
            return null;
        }

        return $this->activeQueueQuery()
            ->where('sexo', $entry->sexo)
            ->where(function ($query) use ($entry): void {
                $query
                    ->where('created_at', '<', $entry->created_at)
                    ->orWhere(function ($query) use ($entry): void {
                        $query
                            ->where('created_at', $entry->created_at)
                            ->where('id', '<=', $entry->id);
                    });
            })
            ->count();
    }

    public function createPublicEntry(array $data): WaitlistEntry
    {
        $normalizedPhone = $this->normalizePhone($data['telefone'] ?? null);

        if ($normalizedPhone !== null && $this->hasActiveDuplicate($normalizedPhone, (string) $data['sexo'])) {
            throw ValidationException::withMessages([
                'telefone' => 'Este telefone já está na fila de espera para o sexo selecionado.',
            ]);
        }

        return WaitlistEntry::query()->create([
            'nome' => trim((string) $data['nome']),
            'telefone' => (string) $data['telefone'],
            'telefone_normalizado' => $normalizedPhone,
            'email' => filled($data['email'] ?? null) ? trim((string) $data['email']) : null,
            'sexo' => (string) $data['sexo'],
            'data_nascimento' => $this->birthDate($data['data_nascimento'] ?? null),
            'observacao' => filled($data['observacao'] ?? null) ? trim((string) $data['observacao']) : null,
            'status' => WaitlistEntryStatus::Aguardando,
            'accepted_privacy_at' => now(),
        ]);
    }

    public function generateInvitation(WaitlistEntry $entry, User $user): string
    {
        return DB::transaction(function () use ($entry, $user): string {
            $entry->refresh();

            if (! $this->canGenerateInvitation($entry)) {
                throw ValidationException::withMessages([
                    'waitlist' => 'Esta pessoa não pode ser convocada no status atual.',
                ]);
            }

            if (! $this->hasVacancyForInvitation($entry)) {
                throw ValidationException::withMessages([
                    'waitlist' => 'Não há vaga disponível para este sexo no momento.',
                ]);
            }

            $token = Str::random(64);
            $expiresAt = now()->addHours($this->invitationHours());

            $entry->forceFill([
                'status' => WaitlistEntryStatus::Convocado,
                'invitation_token_hash' => hash('sha256', $token),
                'invitation_token_encrypted' => Crypt::encryptString($token),
                'invitation_generated_at' => now(),
                'invitation_generated_by' => $user->getKey(),
                'invitation_expires_at' => $expiresAt,
            ])->save();

            return $this->invitationUrl($entry->fresh(), $token);
        });
    }

    public function invitationUrl(WaitlistEntry $entry, ?string $token = null): string
    {
        $token ??= Crypt::decryptString((string) $entry->invitation_token_encrypted);

        return URL::temporarySignedRoute(
            'waitlist.invitation.show',
            $entry->invitation_expires_at ?? now()->addHours($this->invitationHours()),
            [
                'waitlistEntry' => $entry,
                'token' => $token,
            ],
        );
    }

    public function whatsappUrl(WaitlistEntry $entry): ?string
    {
        if (blank($entry->telefone_normalizado) || blank($entry->invitation_token_encrypted)) {
            return null;
        }

        $message = sprintf(
            'Olá %s! Surgiu uma vaga para o Acampamento Juvenil. Complete sua inscrição pelo link: %s',
            $entry->nome,
            $this->invitationUrl($entry),
        );

        return 'https://wa.me/'.$entry->telefone_normalizado.'?text='.rawurlencode($message);
    }

    public function invitationCanBeUsed(WaitlistEntry $entry, string $token): bool
    {
        return $entry->status === WaitlistEntryStatus::Convocado
            && filled($entry->invitation_token_hash)
            && hash_equals((string) $entry->invitation_token_hash, hash('sha256', $token))
            && $entry->invitation_expires_at !== null
            && $entry->invitation_expires_at->isFuture();
    }

    public function markInscribed(WaitlistEntry $entry, Campista $campista): void
    {
        $entry->forceFill([
            'status' => WaitlistEntryStatus::Inscrito,
            'campista_id' => $campista->getKey(),
            'invitation_accepted_at' => now(),
        ])->save();
    }

    public function expireOverdueInvitations(): void
    {
        WaitlistEntry::query()
            ->where('status', WaitlistEntryStatus::Convocado->value)
            ->whereNotNull('invitation_expires_at')
            ->where('invitation_expires_at', '<=', now())
            ->update(['status' => WaitlistEntryStatus::Expirado->value]);
    }

    public function hasActiveDuplicate(string $normalizedPhone, string $sex): bool
    {
        return $this->activeQueueQuery()
            ->where('telefone_normalizado', $normalizedPhone)
            ->where('sexo', $sex)
            ->exists();
    }

    public function canGenerateInvitation(WaitlistEntry $entry): bool
    {
        return in_array($entry->status, [WaitlistEntryStatus::Aguardando, WaitlistEntryStatus::Expirado], true);
    }

    public function hasVacancyForInvitation(WaitlistEntry $entry): bool
    {
        $settings = app(GeneralSettings::class)->toArray();
        $sex = (string) $entry->sexo;

        if ($this->hasSexSpecificLimits($settings)) {
            $limit = $this->limit($settings['qtd_max_vagas_'.$this->sexSettingSuffix($sex)] ?? null);

            if ($limit === null) {
                return true;
            }

            return ($this->activeCampistaCountForSex($sex) + $this->activeReservationsForSex($sex, $entry)) < $limit;
        }

        $limit = $this->limit($settings['qtd_max_vagas'] ?? null);

        if ($limit === null) {
            return true;
        }

        return ($this->activeCampistaCount() + $this->activeReservations($entry)) < $limit;
    }

    private function activeQueueQuery()
    {
        return WaitlistEntry::query()
            ->where(function ($query): void {
                $query
                    ->where('status', WaitlistEntryStatus::Aguardando->value)
                    ->orWhere(function ($query): void {
                        $query
                            ->where('status', WaitlistEntryStatus::Convocado->value)
                            ->where('invitation_expires_at', '>', now());
                    });
            });
    }

    private function activeReservationQuery()
    {
        return WaitlistEntry::query()
            ->where('status', WaitlistEntryStatus::Convocado->value)
            ->where('invitation_expires_at', '>', now());
    }

    private function isActiveQueueEntry(WaitlistEntry $entry): bool
    {
        return $entry->status === WaitlistEntryStatus::Aguardando
            || ($entry->status === WaitlistEntryStatus::Convocado && $entry->invitation_expires_at?->isFuture());
    }

    private function invitationHours(): int
    {
        return max(1, (int) (app(GeneralSettings::class)->waitlist_invitation_hours ?? 24));
    }

    private function activeCampistaCount(): int
    {
        return Campista::query()
            ->where('status', '<>', StatusInscricao::Cancelado->value)
            ->count();
    }

    private function activeCampistaCountForSex(string $sex): int
    {
        return Campista::query()
            ->where('status', '<>', StatusInscricao::Cancelado->value)
            ->where('form_data->sexo', $sex)
            ->count();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function hasSexSpecificLimits(array $settings): bool
    {
        return $this->limit($settings['qtd_max_vagas_masculino'] ?? null) !== null
            || $this->limit($settings['qtd_max_vagas_feminino'] ?? null) !== null;
    }

    private function sexSettingSuffix(string $sex): string
    {
        return $sex === 'M' ? 'masculino' : 'feminino';
    }

    private function limit(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $limit = (int) $value;

        return $limit > 0 ? $limit : null;
    }

    private function birthDate(mixed $value): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('d/m/Y', $value);

        return $date instanceof CarbonImmutable ? $date->toDateString() : null;
    }
}
