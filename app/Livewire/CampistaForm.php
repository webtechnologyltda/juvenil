<?php

namespace App\Livewire;

use App\Jobs\SendNewRegistrationNotification;
use App\Models\Campista;
use App\Models\WaitlistEntry;
use App\Settings\GeneralSettings;
use App\Support\CampistaRegistrationAvailability;
use App\Support\Campistas\WaitlistManager;
use App\Support\FilamentUploadState;
use App\Support\RegistrationAgeLimits;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use JeffersonGoncalves\Filament\CepField\Forms\Components\CepInput;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;
use Throwable;

class CampistaForm extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use RestrictsFileUploadsToSchemaComponents;

    public ?array $data = [];

    #[Computed]
    public ?array $settings = [];

    #[Session]
    public $comprado = false;

    public ?WaitlistEntry $waitlistEntry = null;

    public ?string $waitlistToken = null;

    public function render()
    {
        return view('livewire.campista-form');
    }

    public function mount(?WaitlistEntry $waitlistEntry = null, ?string $token = null)
    {
        $this->settings = app(GeneralSettings::class)->toArray();
        $this->waitlistEntry = $waitlistEntry;
        $this->waitlistToken = $token;
        // pega o valor de comprado do localstorage
        $this->getForm('form')->fill($this->waitlistFormDefaults());
    }

    #[Computed]
    public function availability(): CampistaRegistrationAvailability
    {
        return CampistaRegistrationAvailability::fromSettings($this->settings ?? []);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 3,
                        'lg' => 3,
                        'xl' => 3,
                    ])
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->hiddenLabel()
                            ->label('Foto de identificação')
                            ->placeholder(fn () => new HtmlString('<span><a class="text-primary-600 font-bold">Clique aqui</a></br>Para adicionar uma foto sua</span>'))
                            ->alignCenter()
                            ->disk('public')
                            ->directory('foto-formulario')
                            ->columnSpan(1)
                            ->columnStart([
                                'md' => 2,
                                'lg' => 2,
                                'xl' => 2,
                            ])
                            ->image()
                            ->imageAspectRatio('1:1')
                            ->automaticallyCropImagesToAspectRatio()
                            ->automaticallyResizeImagesMode('cover')
                            ->automaticallyResizeImagesToWidth('500')
                            ->automaticallyResizeImagesToHeight('500')
                            ->automaticallyUpscaleImagesWhenResizing(false)
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                            ])
                            ->rules(['mimes:jpg,jpeg,png,webp'])
                            ->preventFilePathTampering(
                                allowFilePathUsing: fn (string $file): bool => str_starts_with($file, 'foto-formulario/'),
                            )
                            ->loadingIndicatorPosition('center')
                            ->panelAspectRatio('1:1')
                            ->itemPanelAspectRatio('1:1')
                            ->removeUploadedFileButtonPosition('top-center')
                            ->uploadProgressIndicatorPosition('center')
                            ->extraAttributes(['class' => 'juvenil-photo-upload'])
                            ->imagePreviewHeight('250')
                            ->panelLayout('integrated')
                            ->validationMessages([
                                'mimetypes' => 'Envie uma imagem nos formatos JPG, JPEG, PNG ou WEBP.',
                                'mimes' => 'Envie uma imagem nos formatos JPG, JPEG, PNG ou WEBP.',
                            ])
                            ->required(),
                    ]),
                Placeholder::make('mensagem_foto')
                    ->hiddenLabel()
                    ->hintColor('primary')
                    ->hintIcon('heroicon-o-exclamation-circle')
                    ->columnSpanFull()
                    ->hint('Por favor, envie uma foto SEM óculos escuros ou acessórios que possam dificultar a sua identificação.'),
                Fieldset::make('Informações Pessoais')
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 3,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextInput::make('nome')
                            ->label('Nome Completo')
                            ->required()
                            ->disabled(fn (): bool => $this->hasWaitlistInvitation())
                            ->dehydrated()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 3,
                                'md' => 3,
                                'xl' => 3,
                            ])
                            ->maxLength(255),
                        TextInput::make('form_data.data_nacimento')
                            ->required()
                            ->mask('99/99/9999')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 1,
                            ])
                            ->label('Data de Nascimento'),

                        ToggleButtons::make('form_data.sexo')
                            ->required()
                            ->disabled(fn (): bool => $this->hasWaitlistInvitation())
                            ->dehydrated()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ])
                            ->inline()
                            ->inlineLabel(false)
                            ->options([
                                'M' => 'Masculino',
                                'F' => 'Feminino',
                            ])
                            ->colors([
                                'M' => Color::Blue,
                                'F' => Color::Pink,
                            ])
                            ->icons([
                                'M' => 'eos-male',
                                'F' => 'eos-female',
                            ])
                            ->disableOptionWhen(fn (string $value): bool => ! $this->hasWaitlistInvitation() && ! $this->availability->sexHasVacancy($value))
                            ->label('Sexo'),

                        TextInput::make('form_data.altura')
                            ->integer()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 1,
                            ])
                            ->suffix('cm')
                            ->label('Altura'),

                        TextInput::make('form_data.peso')
                            ->integer()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 1,
                            ])
                            ->suffix('kg')
                            ->label('Peso'), ]),
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 3,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextInput::make('form_data.rede_social')
                            ->placeholder('Instagram, Facebook, etc...')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                                'xl' => 2,
                            ])
                            ->label('Rede Social'),

                        TextInput::make('form_data.telefone_campista')
                            ->mask('(99) 9 9999-9999')
                            ->required()
                            ->disabled(fn (): bool => $this->hasWaitlistInvitation())
                            ->dehydrated()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ])
                            ->label('Telefone Campista'),

                    ]),
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 3,
                        'xl' => 4,
                    ])->schema([

                        Html::make(new HtmlString('<p class="text-sm text-primary-600">Informe os dados de uma pessoa responsável de fora, incluindo o nome e o telefone dela.</p>'))
                            ->columnSpanFull(),
                        TextInput::make('form_data.telefone_reponsavel_1')
                            ->required()
                            ->mask('(99) 9 9999-9999')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ])
                            ->label('Telefone Responsável'),

                        TextInput::make('form_data.telefone_reponsavel_nome_1')
                            ->label('Nome do Contato')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ]),

                        ToggleButtons::make('form_data.toma_remedio')
                            ->label('Toma algum Remédio?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->colors([
                                true => 'success',
                                false => 'danger',
                            ])
                            ->options([
                                true => 'Sim',
                                false => 'Não',
                            ]),

                        Textarea::make('form_data.remedio')
                            ->rows(3)
                            ->required(fn (Get $get) => $get('form_data.toma_remedio') == true)
                            ->visible(fn (Get $get) => $get('form_data.toma_remedio') == true)
                            ->label('Por favor, descreva os medicamentos abaixo e os horários de administração caso se aplique')
                            ->columnSpanFull(),

                        ToggleButtons::make('form_data.tem_recomendacao')
                            ->label('Tem alguma recomendação especial?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->colors([
                                true => 'success',
                                false => 'danger',
                            ])
                            ->options([
                                true => 'Sim',
                                false => 'Não',
                            ]),

                        Textarea::make('form_data.recomendacao')
                            ->label('Qual?')
                            ->required(fn (Get $get) => $get('form_data.tem_recomendacao') == true)
                            ->visible(fn (Get $get) => $get('form_data.tem_recomendacao') == true)
                            ->rows(3)
                            ->columnSpanFull(),

                        ToggleButtons::make('form_data.tamanho_camiseta')
                            ->label('Tamanho da camiseta')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 4,
                                'xl' => 4,
                            ])
                            ->options([
                                '14' => '14',
                                'PP' => 'PP',
                                'P' => 'P',
                                'M' => 'M',
                                'G' => 'G',
                                'GG' => 'GG',
                                'EG' => 'EG',
                                'X1' => 'X1',
                                'O' => 'Outros',
                            ])
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->required(),

                        TextInput::make('form_data.tamanho_camiseta_outro')
                            ->label('Tamanho da camiseta:')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 1,
                            ])
                            ->required()
                            ->visible(fn (Get $get) => $get('form_data.tamanho_camiseta') == 'O')
                            ->requiredIf('tamanho_camiseta', fn (Get $get) => $get('form_data.tamanho_camiseta') == 'O')
                            ->minLength(1)
                            ->maxLength(3),
                    ]),

                Fieldset::make('Endereço')
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 3,
                        'xl' => 5,
                    ])
                    ->schema([

                        Html::make(new HtmlString('<p class="text-sm text-primary-600">Informe o CEP para preencher os campos de endereço automaticamente. Clique na lupa para localizar o endereço.</p>'))
                            ->columnSpanFull(),

                        CepInput::make('form_data.cep')
                            ->label('CEP')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ])
                            ->setMode('suffix')
                            ->setActionLabel('Buscar CEP')
                            ->setActionLabelHidden(true)
                            ->setErrorMessage('CEP inválido.')
                            ->setStreetField('form_data.rua')
                            ->setNeighborhoodField('form_data.bairro')
                            ->setCityField('form_data.cidade')
                            ->setStateField('form_data.estado'),

                        Hidden::make('breakLineEndereco')->columnSpan(1),

                        TextInput::make('form_data.rua')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 4,
                            ])
                            ->label('Rua'),

                        TextInput::make('form_data.numero')
                            ->required()
                            ->numeric()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 1,
                            ])
                            ->label('Número'),
                        TextInput::make('form_data.ponto_referencia')
                            ->label('Complemento')
                            ->minValue(0)
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ]),

                        TextInput::make('form_data.bairro')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 3,
                            ])
                            ->label('Bairro'),

                        TextInput::make('form_data.cidade')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 4,
                                'xl' => 3,
                            ])
                            ->readOnly()
                            ->label('Cidade'),

                        TextInput::make('form_data.estado')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                                'xl' => 2,
                            ])
                            ->readOnly()
                            ->label('Estado'),

                    ]),
                Fieldset::make('Informações importantes')
                    ->columns(4)
                    ->schema([

                        ToggleButtons::make('form_data.paroquia')
                            ->label('Qual paróquia participa?')
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->options([
                                0 => 'Paróquia São Domingos e Nossa Senhora do Carmo',
                                1 => 'Paróquia Santa Luzia',
                                2 => 'Outra paróquia',
                            ])
                            ->reactive() // Garante que o valor seja atualizado em tempo real
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('form_data.paroquia_visible_luzia', $state == 1);
                                $set('form_data.paroquia_visible_carmo', $state == 0);

                            }),

                        ToggleButtons::make('form_data.comunidade')
                            ->label('Qual comunidade?')
                            ->live()
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::selectedParishIs($get, 0))
                            ->required()
                            ->gridDirection('row')
                            ->columns(2)
                            ->options([
                                'Comunidade Matriz de São Domingos e Nossa Senhora do Carmo',
                                'Comunidade Nossa Senhora das Graças',
                                'Comunidade São Paulo',
                                'Comunidade Nossa Senhora do Rosário',
                                'Comunidade Imaculado Coração de Maria',
                            ]),

                        ToggleButtons::make('form_data.comunidade')
                            ->label('Qual comunidade?')
                            ->live()
                            ->columns(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::selectedParishIs($get, 1))
                            ->required()
                            ->gridDirection('row')
                            ->options([
                                'Comunidade Santa Luzia - Machados',
                                'Comunidade Santa Teresinha',
                                'Comunidade São Francisco',
                                'Comunidade Sagrado Coração',
                                'Comunidade Nossa Senhora de Fátima',
                                'Comunidade Santo Agostinho',
                                'Comunidade São José',
                                'Comunidade Nossa Senhora Aparecida',
                            ]),

                        TextInput::make('form_data.comunidade')
                            ->label('Especificar o nome da comunidade')
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::selectedParishIs($get, 2)),

                        ToggleButtons::make('form_data.ja_participou_retiro')
                            ->label('Já participou de algum acampamento/retiro ?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->colors([
                                true => 'success',
                                false => 'danger',
                            ])
                            ->options([
                                true => 'Sim',
                                false => 'Não',
                            ]),

                        TagsInput::make('form_data.retiro_que_participou')
                            ->label('Qual?')
                            ->placeholder('Especifique o qual acampamento')
                            ->columnSpanFull()
                            ->requiredIf(fn (Get $get) => $get('form_data.ja_participou_retiro'), false)
                            ->visible(fn (Get $get) => $get('form_data.ja_participou_retiro') ?? false),

                        ToggleButtons::make('form_data.algum_parente')
                            ->label('Tem algum amigo/parente próximo que irá participar do acampamento ?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->colors([
                                true => 'success',
                                false => 'danger',
                            ])
                            ->options([
                                true => 'Sim',
                                false => 'Não',
                            ]),
                        TagsInput::make('form_data.algum_parente_participante')
                            ->label('Especificar o nome')
                            ->placeholder('Especifique o qual acampamento')
                            ->columnSpanFull()
                            ->requiredIf(fn (Get $get) => $get('form_data.algum_parente'), false)
                            ->visible(fn (Get $get) => $get('form_data.algum_parente') ?? false),

                        ToggleButtons::make('form_data.declaro')
                            ->label('Declaro nunca ter participado de nenhuma edição do Acampamento Juvenil ?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->colors([
                                true => 'success',
                                false => 'danger',
                            ])
                            ->options([
                                true => 'Declaro nunca ter participado',
                                false => 'Não, já participei de alguma edição',
                            ]),

                        Placeholder::make('info_termo')
                            ->hint('Necessario aceitar os termos abaixo, para finalizar a inscrição.')
                            ->hintColor('primary')
                            ->hintIcon('heroicon-o-exclamation-circle')
                            ->columnSpanFull(4)
                            ->hiddenLabel(),

                        Checkbox::make('form_data.aceite_termo_inscricao')
                            ->label(new HtmlString('Eu aceito os <a href="/termos-inscricao" target="_blank" class="text-primary-600 font-bold">Termos de Inscrição</a>'))
                            ->columnSpanFull(),
                        Checkbox::make('form_data.aceitar_politica_privacidade')
                            ->label(new HtmlString('Eu aceito a <a href="/politica-privacidade" target="_blank" class="text-primary-600 font-bold">Politica de Privacidade</a>'))
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    private static function selectedParishIs(Get $get, int $parish): bool
    {
        $selectedParish = $get('form_data.paroquia');

        return $selectedParish !== null
            && $selectedParish !== ''
            && (int) $selectedParish === $parish;
    }

    public function submitForm(): void
    {
        $availability = CampistaRegistrationAvailability::fromSettings(app(GeneralSettings::class));

        if (! $this->hasWaitlistInvitation() && ! $availability->registrationOpen()) {
            Notification::make()
                ->title('Inscrições encerradas')
                ->body($availability->unavailableRegistrationMessage())
                ->duration(60000)
                ->danger()
                ->send();

            return;
        }

        $selectedSex = data_get($this->data, 'form_data.sexo');

        if (! $this->hasWaitlistInvitation() && $selectedSex !== null && $selectedSex !== '' && ! $availability->sexHasVacancy($selectedSex)) {
            Notification::make()
                ->title('Vagas indisponíveis')
                ->body($availability->unavailableSelectedSexMessage($selectedSex))
                ->duration(60000)
                ->danger()
                ->send();

            return;
        }

        $this->validate();

        $availability = CampistaRegistrationAvailability::fromSettings(app(GeneralSettings::class));

        if (! $this->hasWaitlistInvitation() && ! $availability->registrationOpen()) {
            Notification::make()
                ->title('Inscrições encerradas')
                ->body($availability->unavailableRegistrationMessage())
                ->duration(60000)
                ->danger()
                ->send();

            return;
        }

        if (! $this->hasWaitlistInvitation() && ! $availability->sexHasVacancy($selectedSex)) {
            Notification::make()
                ->title('Vagas indisponíveis')
                ->body($availability->unavailableSelectedSexMessage($selectedSex))
                ->duration(60000)
                ->danger()
                ->send();

            return;
        }

        $ageLimitMessage = RegistrationAgeLimits::fromSettings(app(GeneralSettings::class))
            ->violationMessage(data_get($this->data, 'form_data.data_nacimento'));

        if ($ageLimitMessage !== null) {
            Notification::make()
                ->title('Inscrição não permitida')
                ->body($ageLimitMessage)
                ->duration(60000)
                ->danger()
                ->send();

            return;
        }

        if (! $this->data['form_data']['declaro']) {
            Notification::make()
                ->title('Inscrição não permitida')
                ->body('A inscrição não pode ser registrada, pois você já participou do Acampamento Juvenil antes.')
                ->duration(60000)
                ->danger()
                ->send();

            return;
        }
        if (
            ! array_key_exists('aceite_termo_inscricao', $this->data['form_data']) ||
            ! $this->data['form_data']['aceite_termo_inscricao'] ||
            ! array_key_exists('aceitar_politica_privacidade', $this->data['form_data']) ||
            ! $this->data['form_data']['aceitar_politica_privacidade']
        ) {

            Notification::make()
                ->title('Inscrição não permitida')
                ->body(new HtmlString('Para poder realizar a inscrição, é necessário ler e aceitar os
                    <a href="/termos-inscricao" target="_blank" class="text-primary-600 font-bold">Termos de Inscrição</a> e a nossa
                    <a href="/politica-privacidade" target="_blank" class="text-primary-600 font-bold">Política de Privacidade!</a>'))
                ->duration(60000)
                ->danger()
                ->send();

            return;
        }
        try {

            $this->data = Arr::only($this->data, ['nome', 'avatar_url', 'form_data']);

            if ($this->hasWaitlistInvitation()) {
                if (! app(WaitlistManager::class)->invitationCanBeUsed($this->waitlistEntry, (string) $this->waitlistToken)) {
                    Notification::make()
                        ->title('Convite indisponível')
                        ->body('Este link da fila de espera expirou ou já foi usado.')
                        ->danger()
                        ->send();

                    return;
                }

                $this->data['nome'] = $this->waitlistEntry->nome;
                data_set($this->data, 'form_data.sexo', $this->waitlistEntry->sexo);
                data_set($this->data, 'form_data.telefone_campista', $this->waitlistEntry->telefone);
            }

            $this->data['avatar_url'] = FilamentUploadState::storedPath($this->data['avatar_url'] ?? null, 'foto-formulario');

            $campista = Campista::create($this->data);

            if ($this->hasWaitlistInvitation()) {
                app(WaitlistManager::class)->markInscribed($this->waitlistEntry, $campista);
            }

            $this->comprado = true;
            $this->dispatch('inscricao-realizada');
            Notification::make()
                ->title('Registramos a sua inscrição')
                ->body('Já estamos com a sua ficha de inscrição, mas fique atento, para confirmar a sua presença leia atentamente as informações abaixo sobre o pagamento.')
                ->duration(60000)
                ->success()
                ->send();
            $this->reset(['data']);

            $this->dispatchNewRegistrationNotification($campista);

        } catch (\Exception $exception) {
            report($exception);

            Notification::make()
                ->title('Ops! Algo deu errado')
                ->body('Por favor, tente novamente mais tarde.')
                ->duration(15000)
                ->danger()
                ->send();
        }
    }

    public function compraNovaPassagem()
    {
        $this->comprado = false;
        $this->reset(['data']);
    }

    private function dispatchNewRegistrationNotification(Campista $campista): void
    {
        try {
            SendNewRegistrationNotification::dispatch(Campista::class, $campista->id, $campista->nome)
                ->afterCommit();
        } catch (Throwable $exception) {
            rescue(fn () => report($exception), report: false);
        }
    }

    public function hasWaitlistInvitation(): bool
    {
        return $this->waitlistEntry !== null && filled($this->waitlistToken);
    }

    private function waitlistFormDefaults(): array
    {
        if (! $this->hasWaitlistInvitation()) {
            return [];
        }

        return [
            'nome' => $this->waitlistEntry->nome,
            'form_data' => [
                'sexo' => $this->waitlistEntry->sexo,
                'telefone_campista' => $this->waitlistEntry->telefone,
            ],
        ];
    }
}
