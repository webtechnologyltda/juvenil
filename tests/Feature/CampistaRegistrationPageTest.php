<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Livewire\CampistaForm;
use App\Support\AtendenteWhatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

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
        ->assertDontSee('text-[#f46b12]">Acampamento Juvenil</p>', false)
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
        ->assertSee('Inscrever-se')
        ->assertDontSee('Fé, amizade e uma experiência viva de acampamento.')
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
        ->assertDontSee('Increver-se para Trabalhar')
        ->assertDontSee('Info endereco')
        ->assertDontSee('Ponto Referência')
        ->assertDontSee('duas pessoas responsáveis')
        ->assertSee('uma pessoa responsável')
        ->assertSee('Complemento');
});

it('renders the campista registration route', function () {
    seedGeneralRegistrationSettings();

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Inscrição')
        ->assertSee('Inscrever-se');
});

it('renders the configured payment settings on the campista registration page', function () {
    seedGeneralRegistrationSettings([
        'telefone_atendente' => '(47) 9 9999-9999',
        'valor_acampamento' => 32550,
        'pix_copia_cola' => 'PIX_CONFIGURADO_ACAMPAMENTO_JUVENIL',
        'pix_qr_code' => 'settings/pix/qr-code-juvenil.png',
        'termo_responsabilidade' => 'settings/termos/termo-juvenil.pdf',
    ]);

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('R$ 325,50')
        ->assertSee('PIX_CONFIGURADO_ACAMPAMENTO_JUVENIL')
        ->assertSee('settings/pix/qr-code-juvenil.png')
        ->assertSee('/storage/settings/termos/termo-juvenil.pdf', false)
        ->assertSee('Termo de responsabilidade')
        ->assertDontSee('/pdf/termo.pdf', false)
        ->assertSee('https://wa.me/5547999999999', false);
});

it('does not render legacy hard-coded payment data when payment settings are empty', function () {
    seedGeneralRegistrationSettings();

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertDontSee('R$ 250,00')
        ->assertDontSee('qr_code_pix.png')
        ->assertDontSee('00020126910014br.gov.bcb.pix')
        ->assertDontSee('Diocese de Blumenau')
        ->assertDontSee('03.925.280/0035-86');
});

it('uses panel payment settings in the post-registration payment block', function () {
    $postRegistrationView = file_get_contents(resource_path('views/livewire/campista-form.blade.php'));

    expect($postRegistrationView)
        ->toContain("\$this->settings['pix_copia_cola']")
        ->toContain("\$this->settings['pix_qr_code']")
        ->toContain("\$this->settings['valor_acampamento']")
        ->toContain("\$this->settings['termo_responsabilidade']")
        ->toContain('ConfiguredStorageFile::publicUrl')
        ->toContain("AtendenteWhatsapp::url(\$this->settings['telefone_atendente'] ?? null)")
        ->not->toContain("route('pdf.show'")
        ->not->toContain('00020126910014br.gov.bcb.pix')
        ->not->toContain("asset('img/qr_code_pix.png')")
        ->not->toContain('?? 25000');
});

it('uses the configured attendant phone in the post-registration WhatsApp button', function () {
    seedGeneralRegistrationSettings([
        'telefone_atendente' => '+55 (47) 9 8888-7777',
        'termo_responsabilidade' => 'settings/termos/termo-pos-inscricao.pdf',
    ]);

    Livewire::test(CampistaForm::class)
        ->set('comprado', true)
        ->assertSee('Falar com atendente')
        ->assertSee('https://wa.me/5547988887777', false)
        ->assertSee('/storage/settings/termos/termo-pos-inscricao.pdf', false)
        ->assertSee('Termo de responsabilidade');
});

it('normalizes attendant WhatsApp numbers from settings', function () {
    expect(AtendenteWhatsapp::number('(47) 9 9999-9999'))->toBe('5547999999999')
        ->and(AtendenteWhatsapp::number('+55 (47) 9 9999-9999'))->toBe('5547999999999')
        ->and(AtendenteWhatsapp::number(null))->toBeNull()
        ->and(AtendenteWhatsapp::url('(47) 9 9999-9999'))->toStartWith('https://wa.me/5547999999999?text=');
});

function seedGeneralRegistrationSettings(array $overrides = []): void
{
    $settings = array_merge([
        'telefone_atendente' => '(47) 9 9999-9999',
        'valor_acampamento' => null,
        'idade_minima' => 0,
        'idade_maxima' => 0,
        'qtd_max_vagas' => null,
        'qtd_max_vagas_feminino' => null,
        'qtd_max_vagas_masculino' => null,
        'data_inicio_inscricoes' => null,
        'data_final_inscricoes' => null,
        'pix_copia_cola' => null,
        'pix_qr_code' => null,
        'termo_responsabilidade' => null,
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
