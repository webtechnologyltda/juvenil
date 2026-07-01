<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Enums\StatusInscricao;
use App\Enums\WaitlistEntryStatus;
use App\Livewire\CampistaForm;
use App\Livewire\CampistaWaitlistForm;
use App\Models\Campista;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Support\Campistas\WaitlistManager;
use Database\Seeders\CampistaSeeder;
use Database\Seeders\ShieldSeeder;
use Database\Seeders\WaitlistEntrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the waitlist form when campista capacity is full', function () {
    seedWaitlistSettings([
        'qtd_max_vagas_masculino' => 1,
        'qtd_max_vagas_feminino' => 1,
    ]);

    createWaitlistCampistaForSex('M', StatusInscricao::Pago);
    createWaitlistCampistaForSex('F', StatusInscricao::Pago);

    Livewire::test(CampistaForm::class)
        ->assertSee('Limite de inscrições atingido')
        ->assertSee('Entrar na fila')
        ->assertDontSee('Inscrever-se');
});

it('centers the public waitlist action across viewports', function () {
    $view = file_get_contents(resource_path('views/livewire/campista-waitlist-form.blade.php'));

    expect($view)
        ->toContain('<div class="flex justify-center">')
        ->not->toContain('<div class="mt-4 text-left">');
});

it('stores public waitlist entries with general and sex positions', function () {
    seedWaitlistSettings([
        'qtd_max_vagas_feminino' => 1,
    ]);

    createWaitlistCampistaForSex('F', StatusInscricao::Pago);

    Livewire::test(CampistaWaitlistForm::class)
        ->fillForm([
            'nome' => 'Ana da Silva',
            'telefone' => '(47) 9 1111-2222',
            'email' => 'ana@example.com',
            'data_nascimento' => '10/05/2011',
            'sexo' => 'F',
            'observacao' => 'Pode ser avisada pelo WhatsApp.',
            'aceitar_politica_privacidade' => true,
        ])
        ->call('submit')
        ->assertNotified('Você entrou na fila de espera')
        ->assertSee('Você está na posição geral 1')
        ->assertSee('posição 1 da fila por sexo');

    $entry = WaitlistEntry::query()->firstOrFail();

    expect($entry->nome)
        ->toBe('Ana da Silva')
        ->and($entry->telefone_normalizado)
        ->toBe('5547911112222')
        ->and($entry->sexo)
        ->toBe('F')
        ->and($entry->status)
        ->toBe(WaitlistEntryStatus::Aguardando)
        ->and($entry->accepted_privacy_at)
        ->not->toBeNull();
});

it('keeps a single unavailable sex fixed in the waitlist action', function () {
    seedWaitlistSettings([
        'qtd_max_vagas_masculino' => 1,
        'qtd_max_vagas_feminino' => 1,
    ]);

    createWaitlistCampistaForSex('F', StatusInscricao::Pago);

    Livewire::test(CampistaWaitlistForm::class, ['sex' => 'F'])
        ->mountAction('joinWaitlist')
        ->assertSchemaStateSet(['sexo' => 'F'])
        ->fillForm([
            'nome' => 'Ana da Silva',
            'telefone' => '(47) 9 1111-2222',
            'email' => 'ana@example.com',
            'data_nascimento' => '10/05/2011',
            'sexo' => 'M',
            'observacao' => null,
            'aceitar_politica_privacidade' => true,
        ])
        ->callMountedAction()
        ->assertHasFormErrors(['sexo']);

    expect(WaitlistEntry::query()->count())->toBe(0);

    Livewire::test(CampistaWaitlistForm::class, ['sex' => 'F'])
        ->callAction('joinWaitlist', data: [
            'nome' => 'Ana da Silva',
            'telefone' => '(47) 9 1111-2222',
            'email' => 'ana@example.com',
            'data_nascimento' => '10/05/2011',
            'sexo' => 'F',
            'observacao' => null,
            'aceitar_politica_privacidade' => true,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified('Você entrou na fila de espera');

    expect(WaitlistEntry::query()->firstOrFail()->sexo)->toBe('F');
});

it('shows waitlist action validation errors on invalid birthdate and duplicated phone', function () {
    seedWaitlistSettings([
        'qtd_max_vagas_feminino' => 1,
    ]);

    createWaitlistCampistaForSex('F', StatusInscricao::Pago);

    Livewire::test(CampistaWaitlistForm::class, ['sex' => 'F'])
        ->callAction('joinWaitlist', data: [
            'nome' => 'Ana da Silva',
            'telefone' => '(47) 9 1111-2222',
            'email' => 'ana@example.com',
            'data_nascimento' => '29/02/96',
            'sexo' => 'F',
            'observacao' => null,
            'aceitar_politica_privacidade' => true,
        ])
        ->assertHasFormErrors(['data_nascimento']);

    WaitlistEntry::factory()->create([
        'telefone' => '(47) 9 1111-2222',
        'telefone_normalizado' => '5547911112222',
        'sexo' => 'F',
        'status' => WaitlistEntryStatus::Aguardando,
    ]);

    Livewire::test(CampistaWaitlistForm::class, ['sex' => 'F'])
        ->callAction('joinWaitlist', data: [
            'nome' => 'Ana da Silva',
            'telefone' => '(47) 9 1111-2222',
            'email' => 'ana@example.com',
            'data_nascimento' => '10/05/2011',
            'sexo' => 'F',
            'observacao' => null,
            'aceitar_politica_privacidade' => true,
        ])
        ->assertHasFormErrors(['telefone']);
});

it('keeps public registration closed for a sex that has people waiting after a cancellation', function () {
    seedWaitlistSettings([
        'qtd_max_vagas_masculino' => 1,
        'qtd_max_vagas_feminino' => 1,
    ]);

    createWaitlistCampistaForSex('F', StatusInscricao::Cancelado);

    WaitlistEntry::factory()->create([
        'sexo' => 'F',
        'status' => WaitlistEntryStatus::Aguardando,
        'created_at' => now()->subMinute(),
    ]);

    Livewire::test(CampistaForm::class)
        ->set('data.form_data.sexo', 'F')
        ->assertSee('Não há vagas disponíveis para o sexo feminino.')
        ->assertSee('campista-waitlist-form');
});

it('generates a signed invitation link and completes a campista registration from it', function () {
    Storage::fake('public');

    seedWaitlistSettings([
        'qtd_max_vagas_masculino' => 1,
        'qtd_max_vagas_feminino' => 1,
        'waitlist_invitation_hours' => 24,
    ]);

    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');
    $this->actingAs($user);

    $entry = WaitlistEntry::factory()->create([
        'nome' => 'Ana Convocada',
        'telefone' => '(47) 9 2222-3333',
        'telefone_normalizado' => '5547922223333',
        'sexo' => 'F',
        'status' => WaitlistEntryStatus::Aguardando,
    ]);

    $url = app(WaitlistManager::class)->generateInvitation($entry, $user);
    $entry->refresh();

    createWaitlistCampistaForSex('M', StatusInscricao::Pago);
    createWaitlistCampistaForSex('F', StatusInscricao::Pago);

    $this->withoutVite();

    $this->get($url)
        ->assertOk()
        ->assertSee('Convite da fila de espera')
        ->assertSee('Ana Convocada')
        ->assertSee('Inscrever-se')
        ->assertSee('filament-registration-shell', false)
        ->assertDontSee('Inscrições abertas')
        ->assertDontSee('Limite de inscrições atingido');

    Livewire::test(CampistaForm::class, [
        'waitlistEntry' => $entry,
        'token' => decryptWaitlistToken($entry),
    ])
        ->set('data', waitlistCampistaPayload([
            'nome' => 'Nome Alterado',
            'avatar_url' => ['campista.png' => 'foto-formulario/campista.png'],
            'form_data' => [
                'sexo' => 'M',
                'telefone_campista' => '(47) 9 9999-9999',
            ],
        ]))
        ->call('submitForm')
        ->assertHasNoFormErrors()
        ->assertNotified('Registramos a sua inscrição');

    $entry->refresh();
    $campista = Campista::query()->findOrFail($entry->campista_id);

    expect($campista->nome)
        ->toBe('Ana Convocada')
        ->and(data_get($campista->form_data, 'sexo'))
        ->toBe('F')
        ->and(data_get($campista->form_data, 'telefone_campista'))
        ->toBe('(47) 9 2222-3333')
        ->and($entry->status)
        ->toBe(WaitlistEntryStatus::Inscrito)
        ->and($entry->campista_id)
        ->toBe($campista->getKey());
});

it('renders the administrative waitlist page with queue entries', function () {
    seedWaitlistSettings();
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    WaitlistEntry::factory()->create([
        'nome' => 'Primeira Mulher',
        'sexo' => 'F',
        'status' => WaitlistEntryStatus::Aguardando,
        'created_at' => now()->subMinute(),
    ]);

    WaitlistEntry::factory()->create([
        'nome' => 'Primeiro Homem',
        'sexo' => 'M',
        'status' => WaitlistEntryStatus::Aguardando,
    ]);

    $this->actingAs($user)
        ->get(route('filament.admin.resources.waitlist-entries.index'))
        ->assertOk()
        ->assertSee('Adicionar na Fila de Espera')
        ->assertSee('Primeira Mulher')
        ->assertSee('Primeiro Homem');
});

it('offers invitation sharing from one modal instead of a standalone whatsapp action', function () {
    $table = file_get_contents(app_path('Filament/Resources/WaitlistEntries/Tables/WaitlistEntriesTable.php'));

    expect($table)
        ->toContain("Action::make('sendInvitationLink')")
        ->toContain("->label('Enviar link de inscrição')")
        ->toContain("Action::make('copyInvitationLink')")
        ->toContain("->label('Copiar link')")
        ->toContain("Action::make('sendInvitationWhatsapp')")
        ->toContain("->label('Enviar via WhatsApp')")
        ->not->toContain("Action::make('openWhatsapp')");
});

it('seeds coherent waitlist demo data without duplicating active queue entries', function () {
    $this->seed(CampistaSeeder::class);
    $this->seed(WaitlistEntrySeeder::class);
    $this->seed(WaitlistEntrySeeder::class);

    $entries = WaitlistEntry::query()
        ->where('admin_notes', 'Dados demonstrativos da fila de espera para testes e painel operacional.')
        ->get();

    $activeDuplicates = $entries
        ->whereIn('status', [WaitlistEntryStatus::Aguardando, WaitlistEntryStatus::Convocado])
        ->groupBy(fn (WaitlistEntry $entry): string => $entry->sexo.'|'.$entry->telefone_normalizado)
        ->filter(fn ($group): bool => $group->count() > 1);

    $invited = $entries->firstWhere('status', WaitlistEntryStatus::Convocado);
    $expired = $entries->firstWhere('status', WaitlistEntryStatus::Expirado);
    $inscribed = $entries->firstWhere('status', WaitlistEntryStatus::Inscrito);

    expect($entries)->toHaveCount(11)
        ->and($entries->where('status', WaitlistEntryStatus::Aguardando))->toHaveCount(6)
        ->and($entries->pluck('telefone_normalizado')->filter())->toHaveCount(11)
        ->and($activeDuplicates)->toBeEmpty()
        ->and($invited?->invitation_token_hash)->not->toBeNull()
        ->and($invited?->invitation_expires_at?->isFuture())->toBeTrue()
        ->and($expired?->invitation_token_hash)->not->toBeNull()
        ->and($expired?->invitation_expires_at?->isPast())->toBeTrue()
        ->and($inscribed?->campista_id)->not->toBeNull()
        ->and($inscribed?->invitation_accepted_at)->not->toBeNull();
});

function seedWaitlistSettings(array $overrides = []): void
{
    $settings = array_merge([
        'telefone_atendente' => '(47) 9 9999-9999',
        'valor_acampamento' => null,
        'idade_minima' => 0,
        'idade_maxima' => 0,
        'qtd_max_vagas' => null,
        'qtd_max_vagas_feminino' => null,
        'qtd_max_vagas_masculino' => null,
        'waitlist_invitation_hours' => 24,
        'data_inicio_inscricoes' => null,
        'data_final_inscricoes' => null,
        'pix_copia_cola' => null,
        'pix_qr_code' => null,
        'termo_responsabilidade' => null,
        'atendentes' => [],
        'liberacao_inscricoes_status' => LiberacaoInscricoesStatusEnum::LIBERADO->value,
        'liberacao_inscricoes_equipe_trabalho_status' => LiberacaoInscricoesEquipeTrabalhoStatusEnum::LIBERADO->value,
        'liberacao_inscricoes_bloco' => null,
    ], $overrides);

    foreach ($settings as $name => $payload) {
        DB::table('settings')->updateOrInsert(
            [
                'group' => 'general',
                'name' => $name,
            ],
            [
                'payload' => json_encode($payload),
            ],
        );
    }
}

function createWaitlistCampistaForSex(string $sex, StatusInscricao $status): Campista
{
    return Campista::factory()->create([
        'status' => $status->value,
        'tribo_id' => null,
        'user_id' => null,
        'form_data' => [
            'sexo' => $sex,
        ],
    ]);
}

function decryptWaitlistToken(WaitlistEntry $entry): string
{
    return Crypt::decryptString((string) $entry->invitation_token_encrypted);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function waitlistCampistaPayload(array $overrides = []): array
{
    $payload = [
        'nome' => 'Ana Convocada',
        'avatar_url' => ['campista.png' => 'foto-formulario/campista.png'],
        'form_data' => [
            'data_nacimento' => '10/05/2011',
            'sexo' => 'F',
            'altura' => 160,
            'peso' => 55,
            'rede_social' => '@ana',
            'telefone_campista' => '(47) 9 2222-3333',
            'telefone_reponsavel_1' => '(47) 9 4444-5555',
            'telefone_reponsavel_nome_1' => 'Responsável Ana',
            'toma_remedio' => 0,
            'tem_recomendacao' => 0,
            'tamanho_camiseta' => 'M',
            'cep' => '88375-000',
            'rua' => 'Rua Teste',
            'numero' => '123',
            'ponto_referencia' => 'Casa',
            'bairro' => 'Centro',
            'cidade' => 'Navegantes',
            'estado' => 'SC',
            'paroquia' => 2,
            'comunidade' => 'Comunidade Teste',
            'ja_participou_retiro' => 0,
            'algum_parente' => 0,
            'declaro' => 1,
            'aceite_termo_inscricao' => true,
            'aceitar_politica_privacidade' => true,
        ],
    ];

    return array_replace_recursive($payload, $overrides);
}
