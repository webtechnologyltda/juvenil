<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Filament\Pages\GeneralSettingsPage;
use App\Models\User;
use App\Settings\GeneralSettings;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the general settings page with registration payment controls', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user)
        ->get(route('filament.admin.pages.general-settings-page'))
        ->assertOk()
        ->assertSee('Pagamento PIX')
        ->assertSee('Valor do Acampamento')
        ->assertSee('PIX copia e cola')
        ->assertSee('Imagem do QR Code PIX')
        ->assertSee('Atendimento e Documentos')
        ->assertSee('Atendentes')
        ->assertSee('Adicionar atendente')
        ->assertSee('Documento do termo')
        ->assertSee('Limite de Campistas Homens')
        ->assertSee('Limite de Campistas Mulheres')
        ->assertSee('Início das Inscrições')
        ->assertSee('Fim das Inscrições')
        ->assertDontSee('Mensagem de Bloqueio')
        ->assertDontSee('Conteúdo bloco de inscrições dos campistas');
});

it('renders the general settings page with configured registration dates', function () {
    $this->seed(ShieldSeeder::class);

    DB::table('settings')
        ->where('group', 'general')
        ->where('name', 'data_inicio_inscricoes')
        ->update(['payload' => json_encode('2026-06-10 19:00:00')]);

    DB::table('settings')
        ->where('group', 'general')
        ->where('name', 'data_final_inscricoes')
        ->update(['payload' => json_encode('2026-06-20 19:00:00')]);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user)
        ->get(route('filament.admin.pages.general-settings-page'))
        ->assertOk()
        ->assertSee('Início das Inscrições')
        ->assertSee('Fim das Inscrições');
});

it('organizes the general settings form by operational responsibility', function () {
    $settingsPage = file_get_contents(app_path('Filament/Pages/GeneralSettingsPage.php'));

    $expectedSections = [
        "Section::make('Inscrições dos Campistas')",
        "Section::make('Equipe de Trabalho')",
        "Section::make('Capacidade e Faixa Etária')",
        "Section::make('Período de Inscrição')",
        "Section::make('Pagamento PIX')",
        "Section::make('Atendimento e Documentos')",
    ];

    $sectionPositions = collect($expectedSections)
        ->mapWithKeys(fn (string $section): array => [$section => mb_strpos($settingsPage, $section)])
        ->all();

    expect($sectionPositions)
        ->each->not->toBeFalse()
        ->and(array_values($sectionPositions))
        ->toBe(collect($sectionPositions)->sort()->values()->all())
        ->and($settingsPage)
        ->toContain("'xl' => '8'")
        ->toContain("'xl' => '4'")
        ->toContain("'lg' => '6'")
        ->toContain("'lg' => '3'");
});

it('does not expose the obsolete blocked registration message editor', function () {
    $settingsPage = file_get_contents(app_path('Filament/Pages/GeneralSettingsPage.php'));

    expect($settingsPage)
        ->not->toContain("Section::make('Mensagem de Bloqueio')")
        ->not->toContain("RichEditor::make('liberacao_inscricoes_bloco')")
        ->not->toContain('Conteúdo bloco de inscrições dos campistas')
        ->not->toContain('fileAttachmentsDirectory(\'settings\')');
});

it('configures the pix qr code upload as a square public image preview', function () {
    $settingsPage = file_get_contents(app_path('Filament/Pages/GeneralSettingsPage.php'));

    expect($settingsPage)
        ->toContain("FileUpload::make('pix_qr_code')")
        ->toContain("->directory('settings/pix')")
        ->toContain("->visibility('public')")
        ->toContain('->image()')
        ->toContain("->imageAspectRatio('1:1')")
        ->toContain('->automaticallyCropImagesToAspectRatio()')
        ->toContain("->automaticallyResizeImagesMode('cover')")
        ->toContain("->automaticallyResizeImagesToWidth('600')")
        ->toContain("->automaticallyResizeImagesToHeight('600')")
        ->toContain('->automaticallyUpscaleImagesWhenResizing(false)')
        ->toContain("->panelAspectRatio('1:1')")
        ->toContain("->itemPanelAspectRatio('1:1')")
        ->toContain("->panelLayout('integrated')")
        ->toContain("->imagePreviewHeight('180')")
        ->toContain("'class' => 'juvenil-pix-qr-upload'");
});

it('configures multiple attendance contacts by purpose', function () {
    $settingsPage = file_get_contents(app_path('Filament/Pages/GeneralSettingsPage.php'));
    $attendanceRepeater = str($settingsPage)
        ->between('Repeater::make(\'atendentes\')', 'FileUpload::make(\'termo_responsabilidade\')')
        ->toString();

    expect($settingsPage)->toContain('Repeater::make(\'atendentes\')');

    expect($attendanceRepeater)
        ->toContain('Select::make(\'tipo\')')
        ->toContain("'duvidas' => 'Dúvidas'")
        ->toContain("'comprovante' => 'Comprovante'")
        ->toContain("'necessidade_especifica' => 'Necessidade específica'")
        ->toContain('TextInput::make(\'nome\')')
        ->toContain('PhoneNumber::make(\'telefone\')')
        ->toContain('Textarea::make(\'observacao\')')
        ->toContain('->columnSpanFull()')
        ->not->toContain("'md' => 4")
        ->not->toContain("'xl' => 6");

    expect(substr_count($attendanceRepeater, '->columnSpanFull()'))->toBeGreaterThanOrEqual(4);
});

it('configures explicit manual registration status options', function () {
    $settingsPage = file_get_contents(app_path('Filament/Pages/GeneralSettingsPage.php'));

    expect(LiberacaoInscricoesStatusEnum::configurationOptions())
        ->toBe([
            LiberacaoInscricoesStatusEnum::LIBERADO->value => 'Liberar inscrições',
            LiberacaoInscricoesStatusEnum::TRANCADO->value => 'Trancar inscrições',
            LiberacaoInscricoesStatusEnum::ENCERRADO->value => 'Encerrar inscrições manualmente',
        ])
        ->and(LiberacaoInscricoesEquipeTrabalhoStatusEnum::configurationOptions())
        ->toBe([
            LiberacaoInscricoesEquipeTrabalhoStatusEnum::LIBERADO->value => 'Liberar inscrições',
            LiberacaoInscricoesEquipeTrabalhoStatusEnum::TRANCADO->value => 'Trancar inscrições',
            LiberacaoInscricoesEquipeTrabalhoStatusEnum::ENCERRADO->value => 'Encerrar inscrições manualmente',
        ])
        ->and($settingsPage)
        ->toContain('->options(LiberacaoInscricoesStatusEnum::configurationOptions())')
        ->toContain('->options(LiberacaoInscricoesEquipeTrabalhoStatusEnum::configurationOptions())');
});

it('saves registration status settings as scalar values', function () {
    $this->seed(ShieldSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('Super Administrador');

    $this->actingAs($user);

    Livewire::test(GeneralSettingsPage::class)
        ->fillForm([
            'telefone_atendente' => '(47) 9 9999-9999',
            'valor_acampamento' => 25000,
            'idade_minima' => 12,
            'idade_maxima' => 18,
            'qtd_max_vagas' => 120,
            'qtd_max_vagas_feminino' => 60,
            'qtd_max_vagas_masculino' => 60,
            'data_inicio_inscricoes' => '2026-06-10 19:00:00',
            'data_final_inscricoes' => '2026-06-20 19:30:00',
            'pix_copia_cola' => null,
            'pix_qr_code' => null,
            'termo_responsabilidade' => ['settings/termos/termo-juvenil.pdf'],
            'atendentes' => [
                [
                    'nome' => 'Janaína',
                    'telefone' => '(47) 9 1111-1111',
                    'tipo' => 'duvidas',
                    'observacao' => null,
                ],
                [
                    'nome' => 'Comprovantes',
                    'telefone' => '(47) 9 3333-3333',
                    'tipo' => 'comprovante',
                    'observacao' => 'Envio dos comprovantes PIX.',
                ],
            ],
            'liberacao_inscricoes_status' => LiberacaoInscricoesStatusEnum::ENCERRADO->value,
            'liberacao_inscricoes_equipe_trabalho_status' => LiberacaoInscricoesEquipeTrabalhoStatusEnum::TRANCADO->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(DB::table('settings')
        ->where('group', 'general')
        ->where('name', 'liberacao_inscricoes_status')
        ->value('payload'))->toBe((string) LiberacaoInscricoesStatusEnum::ENCERRADO->value)
        ->and(DB::table('settings')
            ->where('group', 'general')
            ->where('name', 'liberacao_inscricoes_equipe_trabalho_status')
            ->value('payload'))->toBe((string) LiberacaoInscricoesEquipeTrabalhoStatusEnum::TRANCADO->value);

    app()->forgetInstance(GeneralSettings::class);

    $settings = app(GeneralSettings::class);

    expect($settings->data_inicio_inscricoes)
        ->toBeInstanceOf(DateTimeInterface::class)
        ->and($settings->data_inicio_inscricoes->format('Y-m-d H:i:s'))->toBe('2026-06-10 19:00:00')
        ->and($settings->data_final_inscricoes)
        ->toBeInstanceOf(DateTimeInterface::class)
        ->and($settings->data_final_inscricoes->format('Y-m-d H:i:s'))->toBe('2026-06-20 19:30:00')
        ->and($settings->atendentes)
        ->toHaveCount(2)
        ->and($settings->atendentes[1]['tipo'])
        ->toBe('comprovante');
});
