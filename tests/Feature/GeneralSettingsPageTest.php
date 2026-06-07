<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Filament\Pages\GeneralSettingsPage;
use App\Models\User;
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
        ->assertSee('Documento do termo')
        ->assertSee('Limite de Campistas Homens')
        ->assertSee('Limite de Campistas Mulheres')
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
        "Section::make('Mensagem de Bloqueio')",
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
            'data_inicio_inscricoes' => null,
            'data_final_inscricoes' => null,
            'pix_copia_cola' => null,
            'pix_qr_code' => null,
            'termo_responsabilidade' => ['settings/termos/termo-juvenil.pdf'],
            'liberacao_inscricoes_status' => LiberacaoInscricoesStatusEnum::ENCERRADO->value,
            'liberacao_inscricoes_equipe_trabalho_status' => LiberacaoInscricoesEquipeTrabalhoStatusEnum::TRANCADO->value,
            'liberacao_inscricoes_bloco' => 'Inscrições encerradas para campistas.',
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
});
