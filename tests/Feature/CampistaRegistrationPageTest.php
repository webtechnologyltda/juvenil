<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('renders the main page with the campista registration form only', function () {
    seedGeneralRegistrationSettings();

    $this->withoutVite();

    $this->get('/')
        ->assertOk()
        ->assertSee('Inscrição')
        ->assertSee('Acampamento Juvenil')
        ->assertSee('22 a 26 de Julho')
        ->assertSee('juvenil-hero-shell')
        ->assertSee('juvenil-hero-backdrop')
        ->assertSee('juvenil-poster-title')
        ->assertSee('hero-mobile.png')
        ->assertSee('hero-desktop.png')
        ->assertSee('img/logo.png')
        ->assertSee('juvenil-footer')
        ->assertSee('Logo do Acampamento Juvenil')
        ->assertSee('/termos-inscricao')
        ->assertSee('/politica-privacidade')
        ->assertSee('barraca.mp4')
        ->assertSee('autoplay')
        ->assertSee('muted')
        ->assertSee('loop')
        ->assertSee('playsinline')
        ->assertSee('juvenil-page-loader')
        ->assertSee('campfire-loader.gif')
        ->assertSee('juvenil-mobile-bottom-nav')
        ->assertSee('data-mobile-bottom-nav', false)
        ->assertSee('data-mobile-nav-item', false)
        ->assertSee('bi-ticket-perforated-fill')
        ->assertSee('juvenil-experience-section')
        ->assertSee('juvenil-experience-video')
        ->assertSee('juvenil-experience-copy')
        ->assertSee('juvenil-form-shell')
        ->assertSee('overflow-x-clip')
        ->assertSee('lg:order-2')
        ->assertSee('data-gsap-image', false)
        ->assertSee('data-scrub-reveal', false)
        ->assertSee('data-motion-card', false)
        ->assertSee('data-loader-progress', false)
        ->assertSee('Experiência')
        ->assertSee('Acampamento para viver de perto')
        ->assertSee('Ver detalhes')
        ->assertSee('Comprar')
        ->assertSee('bg-[#f46b12]', false)
        ->assertSee('text-primary-600')
        ->assertSee('filament-registration-shell')
        ->assertDontSee('Trekking')
        ->assertDontSee('bg-color1')
        ->assertDontSee('text-yellow-500')
        ->assertDontSee('juvenil-bento-grid')
        ->assertDontSee('<html lang="pt_BR" class="scroll-smooth">', false)
        ->assertDontSee('Offset')
        ->assertDontSee('Proteção e caminho')
        ->assertDontSee('Paróquia São Domingos de Gusmão e Nossa Senhora do Carmo')
        ->assertDontSee('acampamento-juvenil-divulgacao')
        ->assertDontSee('acampamento-juvenil-logo.svg')
        ->assertDontSee('Arte oficial')
        ->assertDontSee('controls', false)
        ->assertDontSee('Inscrição para equipe de trabalho')
        ->assertDontSee('Increver-se para Trabalhar');
});

it('renders the campista registration route', function () {
    seedGeneralRegistrationSettings();

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Inscrição')
        ->assertSee('Comprar');
});

it('renders the configured payment settings on the campista registration page', function () {
    seedGeneralRegistrationSettings([
        'valor_acampamento' => 32550,
        'pix_copia_cola' => 'PIX_CONFIGURADO_ACAMPAMENTO_JUVENIL',
        'pix_qr_code' => 'settings/pix/qr-code-juvenil.png',
    ]);

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('R$ 325,50')
        ->assertSee('PIX_CONFIGURADO_ACAMPAMENTO_JUVENIL')
        ->assertSee('settings/pix/qr-code-juvenil.png');
});

function seedGeneralRegistrationSettings(array $overrides = []): void
{
    $settings = array_merge([
        'telefone_atendente' => '(47) 9 9999-9999',
        'valor_acampamento' => null,
        'qtd_max_vagas' => null,
        'qtd_max_vagas_feminino' => null,
        'qtd_max_vagas_masculino' => null,
        'data_inicio_inscricoes' => null,
        'data_final_inscricoes' => null,
        'pix_copia_cola' => null,
        'pix_qr_code' => null,
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
