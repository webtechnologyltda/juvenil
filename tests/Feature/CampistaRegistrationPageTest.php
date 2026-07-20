<?php

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Enums\StatusInscricao;
use App\Livewire\CampistaForm;
use App\Models\Campista;
use App\Support\AtendenteWhatsapp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
        ->assertSee('hero-mobile.webp')
        ->assertSee('hero-desktop.webp')
        ->assertSee('img/logo.webp')
        ->assertSee('juvenil-footer')
        ->assertSee('Logo do Acampamento Juvenil')
        ->assertDontSee('text-[#f46b12]">Acampamento Juvenil</p>', false)
        ->assertSee('/termos-inscricao')
        ->assertSee('/politica-privacidade')
        ->assertSee('barraca-720p.mp4')
        ->assertSee('hero-desktop.webp')
        ->assertSee('data-lazy-video')
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
        ->assertSee('Local de embarque')
        ->assertSee('Local de Embarque')
        ->assertSee('Mapa do local de embarque')
        ->assertDontSee('Local do Acampamento Juvenil')
        ->assertDontSee('Mapa do local do Acampamento Juvenil')
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

it('shows a countdown before the configured campista registration start date', function () {
    Carbon::setTestNow('2026-06-07 12:00:00');

    seedGeneralRegistrationSettings([
        'data_inicio_inscricoes' => '2026-06-10 19:00:00',
        'data_final_inscricoes' => '2026-06-20 19:00:00',
        'qtd_max_vagas' => 30,
    ]);

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Inscrições em contagem regressiva')
        ->assertSee('As inscrições começam em 10/06/2026 19:00.')
        ->assertSee('Inscrições a partir de 10 de Junho, às 19h.')
        ->assertDontSee('Inscrições a partir de 07 de Junho, após a Santa Missa das 19h30.')
        ->assertSee('data-registration-countdown', false)
        ->assertSee('2026-06-10T19:00:00', false)
        ->assertDontSee('wire:submit.prevent="submitForm"', false);

    Carbon::setTestNow();
});

it('does not submit campista registration before the configured start date', function () {
    Carbon::setTestNow('2026-06-07 12:00:00');

    seedGeneralRegistrationSettings([
        'data_inicio_inscricoes' => '2026-06-10 19:00:00',
        'qtd_max_vagas' => 30,
    ]);

    Livewire::test(CampistaForm::class)
        ->call('submitForm')
        ->assertHasNoErrors();

    expect(Campista::query()->count())->toBe(0);

    Carbon::setTestNow();
});

it('closes campista registration after the configured end date', function () {
    Carbon::setTestNow('2026-06-21 08:00:00');

    seedGeneralRegistrationSettings([
        'data_inicio_inscricoes' => '2026-06-10 19:00:00',
        'data_final_inscricoes' => '2026-06-20 19:00:00',
        'qtd_max_vagas' => 30,
    ]);

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Inscrições encerradas')
        ->assertSee('O período de inscrições encerrou em 20/06/2026 19:00.')
        ->assertDontSee('wire:submit.prevent="submitForm"', false);

    Carbon::setTestNow();
});

it('shows a manual locked registration message when campista registration is blocked', function () {
    seedGeneralRegistrationSettings([
        'liberacao_inscricoes_status' => LiberacaoInscricoesStatusEnum::TRANCADO->value,
        'liberacao_inscricoes_bloco' => null,
    ]);

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Inscrições trancadas')
        ->assertSee('As inscrições estão trancadas no momento.')
        ->assertDontSee('wire:submit.prevent="submitForm"', false)
        ->assertDontSee('Prepare-se para se inscrever')
        ->assertDontSee('Limite de inscrições atingido');
});

it('shows a manual ended registration message when campista registration is closed by settings', function () {
    seedGeneralRegistrationSettings([
        'liberacao_inscricoes_status' => LiberacaoInscricoesStatusEnum::ENCERRADO->value,
        'liberacao_inscricoes_bloco' => '<p>Inscrições encerradas pela coordenação.</p>',
    ]);

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Inscrições encerradas')
        ->assertSee('As inscrições foram encerradas manualmente pela organização.')
        ->assertSee('Inscrições encerradas pela coordenação.')
        ->assertDontSee('wire:submit.prevent="submitForm"', false)
        ->assertDontSee('O período de inscrições encerrou')
        ->assertDontSee('As inscrições foram encerradas pelo número de vagas preenchidas.');
});

it('shows only the remaining registration time during the configured registration period', function () {
    Carbon::setTestNow('2026-06-12 12:00:00');

    seedGeneralRegistrationSettings([
        'data_inicio_inscricoes' => '2026-06-10 19:00:00',
        'data_final_inscricoes' => '2026-06-20 19:00:00',
        'qtd_max_vagas' => 3,
    ]);

    createCampistaRegistrationForSex('M', StatusInscricao::Pago);
    createCampistaRegistrationForSex('F', StatusInscricao::Cancelado);

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Falta para encerrar')
        ->assertSee('As inscrições encerram em 20/06/2026 19:00.')
        ->assertSee('2026-06-20T19:00:00', false)
        ->assertSee('data-registration-countdown', false)
        ->assertSee('2 vagas disponíveis de 3')
        ->assertSee('1 inscrição ativa')
        ->assertSee('wire:submit.prevent="submitForm"', false)
        ->assertSee('Inscrever-se');

    Carbon::setTestNow();
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

it('renders configured attendance contacts by purpose on the campista page', function () {
    seedGeneralRegistrationSettings([
        'telefone_atendente' => null,
        'atendentes' => [
            [
                'nome' => 'Janaína',
                'telefone' => '(47) 9 1111-1111',
                'tipo' => 'duvidas',
                'observacao' => null,
            ],
            [
                'nome' => 'Marcos',
                'telefone' => '(47) 9 2222-2222',
                'tipo' => 'duvidas',
                'observacao' => null,
            ],
            [
                'nome' => 'Financeiro',
                'telefone' => '(47) 9 3333-3333',
                'tipo' => 'comprovante',
                'observacao' => null,
            ],
            [
                'nome' => 'Inclusão',
                'telefone' => '(47) 9 4444-4444',
                'tipo' => 'necessidade_especifica',
                'observacao' => 'Acessibilidade, saúde e necessidades específicas.',
            ],
        ],
    ]);

    $this->withoutVite();

    $this->get(route('campista'))
        ->assertOk()
        ->assertSee('Atendimento para dúvidas')
        ->assertSee('Dúvidas - Janaína')
        ->assertSee('Dúvidas - Marcos')
        ->assertSee('Envio de comprovante')
        ->assertSee('Enviar comprovante - Financeiro')
        ->assertSee('Necessidades específicas')
        ->assertSee('Inclusão')
        ->assertSee('Acessibilidade, saúde e necessidades específicas.')
        ->assertSee('https://wa.me/5547911111111', false)
        ->assertSee('https://wa.me/5547922222222', false)
        ->assertSee('https://wa.me/5547933333333', false)
        ->assertSee('https://wa.me/5547944444444', false)
        ->assertDontSee('Dúvidas - Financeiro');
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
        ->toContain('AtendenteWhatsapp::firstForPurpose')
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
        ->assertSee('Enviar comprovante')
        ->assertSee('https://wa.me/5547988887777', false)
        ->assertSee('/storage/settings/termos/termo-pos-inscricao.pdf', false)
        ->assertSee('Termo de responsabilidade');
});

it('uses the configured proof attendant in the post-registration WhatsApp button', function () {
    seedGeneralRegistrationSettings([
        'telefone_atendente' => '+55 (47) 9 8888-7777',
        'atendentes' => [
            [
                'nome' => 'Dúvidas',
                'telefone' => '(47) 9 1111-1111',
                'tipo' => 'duvidas',
                'observacao' => null,
            ],
            [
                'nome' => 'Comprovantes',
                'telefone' => '(47) 9 3333-3333',
                'tipo' => 'comprovante',
                'observacao' => null,
            ],
        ],
    ]);

    Livewire::test(CampistaForm::class)
        ->set('comprado', true)
        ->assertSee('Enviar comprovante')
        ->assertSee('https://wa.me/5547933333333', false)
        ->assertDontSee('https://wa.me/5547988887777', false);
});

it('normalizes attendant WhatsApp numbers from settings', function () {
    expect(AtendenteWhatsapp::number('(47) 9 9999-9999'))->toBe('5547999999999')
        ->and(AtendenteWhatsapp::number('+55 (47) 9 9999-9999'))->toBe('5547999999999')
        ->and(AtendenteWhatsapp::number(null))->toBeNull()
        ->and(AtendenteWhatsapp::url('(47) 9 9999-9999'))->toStartWith('https://wa.me/5547999999999?text=')
        ->and(AtendenteWhatsapp::forPurpose([
            [
                'nome' => 'Dúvidas',
                'telefone' => '(47) 9 1111-1111',
                'tipo' => 'duvidas',
            ],
            [
                'nome' => 'Comprovantes',
                'telefone' => '(47) 9 3333-3333',
                'tipo' => 'comprovante',
            ],
        ], 'comprovante')[0]['whatsapp_url'])->toStartWith('https://wa.me/5547933333333?text=');
});

it('alerts and disables a sex option when its campista vacancies are full', function () {
    seedGeneralRegistrationSettings([
        'qtd_max_vagas_masculino' => 2,
        'qtd_max_vagas_feminino' => 1,
    ]);

    createCampistaRegistrationForSex('F', StatusInscricao::Pendente);

    $component = Livewire::test(CampistaForm::class);
    $html = $component->html();

    $component
        ->assertSee('Não há vagas disponíveis para o sexo feminino.')
        ->assertDontSee('As inscrições foram encerradas pelo número de vagas preenchidas.');

    expect(campistaSexOptionIsDisabled($html, 'F'))->toBeTrue()
        ->and(campistaSexOptionIsDisabled($html, 'M'))->toBeFalse()
        ->and($html)->toContain('role="alert"')
        ->and($html)->toContain('text-[#052f35]');
});

it('closes campista registration when all sex-specific vacancies are full', function () {
    seedGeneralRegistrationSettings([
        'qtd_max_vagas_masculino' => 1,
        'qtd_max_vagas_feminino' => 1,
    ]);

    createCampistaRegistrationForSex('M', StatusInscricao::Pago);
    createCampistaRegistrationForSex('F', StatusInscricao::Pendente);

    Livewire::test(CampistaForm::class)
        ->assertSee('Inscrições encerradas')
        ->assertSee('As inscrições foram encerradas pelo número de vagas preenchidas.')
        ->assertDontSee('Inscrever-se');
});

it('uses sex-specific configured vacancies instead of a stale legacy total limit', function () {
    seedGeneralRegistrationSettings([
        'qtd_max_vagas' => 1,
        'qtd_max_vagas_masculino' => 2000,
        'qtd_max_vagas_feminino' => 2000,
    ]);

    createCampistaRegistrationForSex('M', StatusInscricao::Pago);
    createCampistaRegistrationForSex('F', StatusInscricao::Pendente);

    Livewire::test(CampistaForm::class)
        ->assertDontSee('As inscrições foram encerradas pelo número de vagas preenchidas.')
        ->assertSee('3998 vagas disponíveis de 4000')
        ->assertSee('Inscrever-se');
});

it('uses the legacy total limit only when no sex-specific vacancies are configured', function () {
    seedGeneralRegistrationSettings([
        'qtd_max_vagas' => 1,
        'qtd_max_vagas_masculino' => null,
        'qtd_max_vagas_feminino' => null,
    ]);

    createCampistaRegistrationForSex('M', StatusInscricao::Cancelado);

    $openComponent = Livewire::test(CampistaForm::class);

    $openComponent
        ->assertDontSee('As inscrições foram encerradas pelo número de vagas preenchidas.')
        ->assertSee('Inscrever-se');

    createCampistaRegistrationForSex('F', StatusInscricao::Pago);

    Livewire::test(CampistaForm::class)
        ->assertSee('Inscrições encerradas')
        ->assertSee('As inscrições foram encerradas pelo número de vagas preenchidas.')
        ->assertDontSee('Inscrever-se');
});

it('does not count cancelled campista registrations against sex vacancies', function () {
    seedGeneralRegistrationSettings([
        'qtd_max_vagas_masculino' => 1,
        'qtd_max_vagas_feminino' => 1,
    ]);

    createCampistaRegistrationForSex('F', StatusInscricao::Cancelado);

    $component = Livewire::test(CampistaForm::class);
    $html = $component->html();

    $component
        ->assertDontSee('Não há vagas disponíveis para o sexo feminino.')
        ->assertDontSee('As inscrições foram encerradas pelo número de vagas preenchidas.');

    expect(campistaSexOptionIsDisabled($html, 'F'))->toBeFalse();
});

it('rejects a campista submission when the selected sex becomes full before submit', function () {
    seedGeneralRegistrationSettings([
        'qtd_max_vagas_masculino' => 2,
        'qtd_max_vagas_feminino' => 1,
    ]);

    $component = Livewire::test(CampistaForm::class)
        ->set('data.form_data.sexo', 'F');

    createCampistaRegistrationForSex('F', StatusInscricao::Pago);

    $component->call('submitForm');

    expect(Campista::query()->count())->toBe(1);
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

function createCampistaRegistrationForSex(string $sex, StatusInscricao $status): Campista
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

function campistaSexOptionIsDisabled(string $html, string $sex): bool
{
    $document = new DOMDocument;

    @$document->loadHTML($html);

    $xpath = new DOMXPath($document);
    $inputs = $xpath->query(sprintf(
        '//input[@type="radio" and @value="%s" and @*[name()="wire:model" and contains(., "form_data.sexo")]]',
        $sex,
    ));

    expect($inputs)->not->toBeFalse()
        ->and($inputs->length)->toBe(1);

    return $inputs->item(0)?->hasAttribute('disabled') ?? false;
}
