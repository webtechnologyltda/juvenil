<?php

namespace Database\Seeders;

use App\Enums\StatusInscricao;
use App\Enums\WaitlistEntryStatus;
use App\Models\Campista;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Support\Campistas\WaitlistManager;
use Database\Seeders\Support\DemoRegistrationData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class WaitlistEntrySeeder extends Seeder
{
    private const DEMO_NOTE = 'Dados demonstrativos da fila de espera para testes e painel operacional.';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manager = app(WaitlistManager::class);
        $admin = $this->adminUser();

        WaitlistEntry::query()
            ->where('admin_notes', self::DEMO_NOTE)
            ->orWhere('observacao', self::DEMO_NOTE)
            ->delete();

        $this->createWaitingEntries($manager);
        $this->createInvitedEntry($manager, $admin);
        $this->createExpiredEntry($manager, $admin);
        $this->createInscribedEntry($manager, $admin);
        $this->createInactiveEntries($manager, $admin);
    }

    private function createWaitingEntries(WaitlistManager $manager): void
    {
        foreach (range(1, 6) as $index) {
            $entry = $manager->createPublicEntry([
                'nome' => sprintf('Fila %s %03d', $index % 2 === 0 ? 'Masculina' : 'Feminina', $index),
                'telefone' => $this->phone($index),
                'email' => sprintf('fila%03d@example.com', $index),
                'sexo' => $index % 2 === 0 ? 'M' : 'F',
                'data_nascimento' => now()->subYears(14 + ($index % 4))->format('d/m/Y'),
                'observacao' => self::DEMO_NOTE,
            ]);

            $entry->forceFill([
                'admin_notes' => self::DEMO_NOTE,
                'created_at' => now()->subDays(12 - $index),
                'updated_at' => now()->subDays(12 - $index),
            ])->save();
        }
    }

    private function createInvitedEntry(WaitlistManager $manager, User $admin): void
    {
        $entry = $manager->createPublicEntry([
            'nome' => 'Fila Convocada 001',
            'telefone' => $this->phone(20),
            'email' => 'fila-convocada@example.com',
            'sexo' => 'F',
            'data_nascimento' => now()->subYears(15)->format('d/m/Y'),
            'observacao' => self::DEMO_NOTE,
        ]);

        $this->applyInvitationState(
            entry: $entry,
            admin: $admin,
            status: WaitlistEntryStatus::Convocado,
            expiresAt: now()->addHours(18),
            generatedAt: now()->subHours(6),
        );
    }

    private function createExpiredEntry(WaitlistManager $manager, User $admin): void
    {
        $entry = $manager->createPublicEntry([
            'nome' => 'Fila Expirada 001',
            'telefone' => $this->phone(21),
            'email' => 'fila-expirada@example.com',
            'sexo' => 'M',
            'data_nascimento' => now()->subYears(16)->format('d/m/Y'),
            'observacao' => self::DEMO_NOTE,
        ]);

        $this->applyInvitationState(
            entry: $entry,
            admin: $admin,
            status: WaitlistEntryStatus::Expirado,
            expiresAt: now()->subDay(),
            generatedAt: now()->subDays(2),
        );
    }

    private function createInscribedEntry(WaitlistManager $manager, User $admin): void
    {
        $entry = $manager->createPublicEntry([
            'nome' => 'Fila Inscrita 001',
            'telefone' => $this->phone(22),
            'email' => 'fila-inscrita@example.com',
            'sexo' => 'F',
            'data_nascimento' => now()->subYears(15)->format('d/m/Y'),
            'observacao' => self::DEMO_NOTE,
        ]);

        $campista = Campista::query()
            ->where('observacoes', DemoRegistrationData::DEMO_OBSERVATION)
            ->where('status', '<>', StatusInscricao::Cancelado->value)
            ->where('form_data->sexo', 'F')
            ->orderBy('id')
            ->first();

        $campista ??= Campista::factory()->create([
            'nome' => $entry->nome,
            'status' => StatusInscricao::Pago->value,
            'observacoes' => DemoRegistrationData::DEMO_OBSERVATION,
            'tribo_id' => null,
            'user_id' => null,
            'form_data' => array_replace(
                Campista::factory()->make()->form_data ?? [],
                [
                    'sexo' => 'F',
                    'telefone_campista' => $entry->telefone,
                    'aceitar_politica_privacidade' => true,
                ],
            ),
        ]);

        $this->applyInvitationState(
            entry: $entry,
            admin: $admin,
            status: WaitlistEntryStatus::Convocado,
            expiresAt: now()->addHours(12),
            generatedAt: now()->subHours(2),
        );

        $manager->markInscribed($entry->fresh(), $campista);

        $entry->fresh()->forceFill([
            'admin_notes' => self::DEMO_NOTE,
            'updated_at' => now()->subHour(),
        ])->save();
    }

    private function createInactiveEntries(WaitlistManager $manager, User $admin): void
    {
        foreach ([
            ['status' => WaitlistEntryStatus::Desistiu, 'name' => 'Fila Desistente 001', 'sex' => 'M', 'phone' => 23],
            ['status' => WaitlistEntryStatus::Cancelado, 'name' => 'Fila Cancelada 001', 'sex' => 'F', 'phone' => 24],
        ] as $data) {
            $entry = $manager->createPublicEntry([
                'nome' => $data['name'],
                'telefone' => $this->phone($data['phone']),
                'email' => sprintf('fila-%s@example.com', Str::slug($data['name'])),
                'sexo' => $data['sex'],
                'data_nascimento' => now()->subYears(15)->format('d/m/Y'),
                'observacao' => self::DEMO_NOTE,
            ]);

            $entry->forceFill([
                'status' => $data['status'],
                'admin_notes' => self::DEMO_NOTE,
                'cancelled_at' => now()->subDays(3),
                'cancelled_by' => $admin->getKey(),
                'updated_at' => now()->subDays(3),
            ])->save();
        }
    }

    private function applyInvitationState(
        WaitlistEntry $entry,
        User $admin,
        WaitlistEntryStatus $status,
        mixed $expiresAt,
        mixed $generatedAt,
    ): void {
        $token = Str::random(64);

        $entry->forceFill([
            'status' => $status,
            'admin_notes' => self::DEMO_NOTE,
            'invitation_token_hash' => hash('sha256', $token),
            'invitation_token_encrypted' => Crypt::encryptString($token),
            'invitation_generated_at' => $generatedAt,
            'invitation_generated_by' => $admin->getKey(),
            'invitation_expires_at' => $expiresAt,
            'updated_at' => $generatedAt,
        ])->save();
    }

    private function adminUser(): User
    {
        return User::query()
            ->where('email', 'admin@admin.com')
            ->orWhereHas('roles', fn ($query) => $query->where('name', 'Super Administrador'))
            ->orderBy('id')
            ->first()
            ?? User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@admin.com',
                'password' => bcrypt('admin'),
            ]);
    }

    private function phone(int $index): string
    {
        return sprintf('(47) 9 %04d-%04d', 2000 + $index, 3000 + $index);
    }
}
