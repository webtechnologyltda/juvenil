<?php

namespace App\Livewire;

use App\Models\Campista;
use App\Models\User;
use App\Settings\GeneralSettings;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Leandrocfe\FilamentPtbrFormFields\Cep;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class CampistaForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    #[Computed]
    public ?array $settings = [];

    #[Session]
    public $comprado = false;


    public function render()
    {
        return view('livewire.campista-form');
    }

    public function mount()
    {
        $this->settings = app(GeneralSettings::class)->toArray();
        //pega o valor de comprado do localstorage
        $this->getForm('form')->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 3,
                        'lg' => 3,
                        'xl' => 3,
                    ])
                    ->schema([
                        Placeholder::make('offset')
                            ->hiddenLabel()
                            ->columns([
                                'default' => 0,
                                'sm' => 0,
                                'md' => 1,
                                'lg' => 1,
                                'xl' => 1,
                            ]),
                        FileUpload::make('avatar_url')
                            ->hiddenLabel()
                            ->label('Foto de identificação')
                            ->optimize('webp')
                            ->placeholder(fn() => new HtmlString('<span><a class="text-primary-600 font-bold">Clique aqui</a></br>Para adicionar uma foto sua</span>'))
                            ->resize(15)
                            ->alignCenter()
                            ->imageEditor()
                            ->directory('foto-formulario')
                            ->columnSpan(1)
                            ->image()
                            ->imagePreviewHeight('400')
                            ->loadingIndicatorPosition('center')
                            ->panelAspectRatio('1:1')
                            ->removeUploadedFileButtonPosition('top-center')
                            ->uploadProgressIndicatorPosition('center')
                            ->imageEditorMode(2)
                            ->imageCropAspectRatio('1:1')
                            ->orientImagesFromExif(false)
                            ->extraAttributes(['rounded'])
                            ->imagePreviewHeight('250')
                            ->imageEditorAspectRatios([
                                '1:1',
                            ])
                            ->panelLayout('integrated')
                            ->uploadingMessage('Uploading attachment...')
                            ->imageEditorEmptyFillColor('#000000')
                            ->required(),
                    ]),
                Placeholder::make('mensagem_foto')
                    ->hiddenLabel()
                    ->hintColor(Color::Yellow)
                    ->hintIcon('heroicon-o-exclamation-circle')
                    ->columnSpanFull()
                    ->hint('Por favor, envie uma foto SEM óculos escuros ou acessórios que possam dificultar a sua identificação.'),
                Grid::make()
                    ->label( 'Informações Pessoais' )
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

                        Radio::make('form_data.sexo')
                            ->required()
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
                            ->label('Peso'),]),
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

                        Placeholder::make('info_endereco')
                            ->hint('Precisaria fornecer os dados de duas pessoas responsáveis de fora, incluindo o nome e o
                                        telefone de cada uma delas.')
                            ->hintColor(Color::Yellow)
                            ->hintIcon('heroicon-o-exclamation-circle')
                            ->hiddenLabel()
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
                            ->label('Nome da Contato')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ]),
                        TextInput::make('form_data.telefone_reponsavel_2')
                            ->required()
                            ->mask('(99) 9 9999-9999')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ])
                            ->label('Telefone Responsável'),

                        TextInput::make('form_data.telefone_reponsavel_nome_2')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ])
                            ->label('Nome do Contato'),


                        Radio::make('form_data.toma_remedio')
                            ->label('Toma algum Remédio?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->options([
                                true => 'Sim',
                                false => 'Não',
                            ]),

                        Textarea::make('form_data.remedio')
                            ->rows(3)
                            ->required(fn(Get $get) => $get('form_data.toma_remedio') == true)
                            ->visible(fn(Get $get) => $get('form_data.toma_remedio') == true)
                            ->label('Por favor, descreva os medicamentos abaixo e os horários de administração caso se aplique')
                            ->columnSpanFull(),

                        Radio::make('form_data.tem_recomendacao')
                            ->label('Tem alguma recomendação especial?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->options([
                                true => 'Sim',
                                false => 'Não',
                            ]),

                        Textarea::make('form_data.recomendacao')
                            ->label('Qual?')
                            ->required(fn(Get $get) => $get('form_data.tem_recomendacao') == true)
                            ->visible(fn(Get $get) => $get('form_data.tem_recomendacao') == true)
                            ->rows(3)
                            ->columnSpanFull(),

                        Radio::make('form_data.tamanho_camiseta')
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
                            ->visible(fn(Get $get) => $get('form_data.tamanho_camiseta') == 'O')
                            ->requiredIf('tamanho_camiseta', fn(Get $get) => $get('form_data.tamanho_camiseta') == 'O')
                            ->minLength(1)
                            ->maxLength(3),
                    ]),

                Grid::make('Endereço')
                    ->columns([
                        'default' => 1,
                        'sm' => 1,
                        'md' => 3,
                        'xl' => 5,
                    ])
                    ->schema([

                        Placeholder::make('info_endereco')
                            ->hint('Informe o CEP para preencher os campos de endereço automaticamente. Clique na lupa para localizar o endereço.')
                            ->hintColor(Color::Yellow)
                            ->hintIcon('heroicon-o-exclamation-circle')
                            ->hiddenLabel()
                            ->columnSpanFull(),

                        Cep::make('form_data.cep')
                            ->label('CEP')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 3,
                                'xl' => 2,
                            ])
                            ->viaCep(
                            // Determines whether the action should be appended to (suffix) or prepended to (prefix) the cep field, or not included at all (none).
                                mode: 'suffix',

                                // Error message to display if the CEP is invalid.
                                errorMessage: 'CEP inválido.',

                                /**
                                 * Other form fields that can be filled by ViaCep.
                                 * The key is the name of the Filament input, and the value is the ViaCep attribute that corresponds to it.
                                 * More information: https://viacep.com.br/
                                 */
                                setFields: [
                                    'form_data.rua' => 'logradouro',
                                    'form_data.numero' => 'numero',
                                    'form_data.ponto_referencia' => 'complemento',
                                    'form_data.bairro' => 'bairro',
                                    'form_data.cidade' => 'localidade',
                                    'form_data.estado' => 'uf'
                                ],
                            ),

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
                            ->label('Ponto Referência')
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
                Grid::make('Informações importantes')
                    ->columns(4)
                    ->schema([

                        Radio::make('form_data.paroquia')
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

                        Radio::make('form_data.comunidade')
                            ->label('Qual comunidade?')
                            ->live()
                            ->columnSpanFull()
                            ->visible(function (Get $get) {
                                return $get('form_data.paroquia') != null && $get('form_data.paroquia') == 0;
                            })
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

                        Radio::make('form_data.comunidade')
                            ->label('Qual comunidade?')
                            ->live()
                            ->columns(2)
                            ->columnSpanFull()
                            ->visible(function (Get $get) {
                                return $get('form_data.paroquia') != null && $get('form_data.paroquia') == 1;
                            })
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
                            ->visible(function (Get $get) {
                                return $get('form_data.paroquia') != null && $get('form_data.paroquia') == 2;
                            }),

                        Radio::make('form_data.ja_participou_retiro')
                            ->label('Já participou de algum acampamento/retiro ?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->options([
                                true => 'Sim',
                                false => 'Não',
                            ]),


                        TagsInput::make('form_data.retiro_que_participou')
                            ->label('Qual?')
                            ->placeholder('Especifique o qual acampamento')
                            ->columnSpanFull()
                            ->requiredIf(fn(Get $get) => $get('form_data.ja_participou_retiro'), false)
                            ->visible(fn(Get $get) => $get('form_data.ja_participou_retiro') ?? false),

                        Radio::make('form_data.algum_parente')
                            ->label('Tem algum amigo/parente próximo que irá participar do acampamento ?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->options([
                                true => 'Sim',
                                false => 'Não',
                            ]),
                        TagsInput::make('form_data.algum_parente_participante')
                            ->label('Especificar o nome')
                            ->placeholder('Especifique o qual acampamento')
                            ->columnSpanFull()
                            ->requiredIf(fn(Get $get) => $get('form_data.algum_parente'), false)
                            ->visible(fn(Get $get) => $get('form_data.algum_parente') ?? false),

                        Radio::make('form_data.declaro')
                            ->label('Declaro nunca ter participado de nenhuma edição do acampamento Trekking ?')
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->inline()
                            ->inlineLabel(false)
                            ->options([
                                true => 'Declaro nunca ter participado',
                                false => 'Não, já participei de alguma edição',
                            ]),



                        Placeholder::make('info_termo')
                            ->hint('Necessario aceitar os termos abaixo, para finalizar a inscrição.')
                            ->hintColor(Color::Yellow)
                            ->hintIcon('heroicon-o-exclamation-circle')
                            ->columnSpanFull(4)
                            ->hiddenLabel(),

                        Checkbox::make('form_data.aceite_termo_inscricao')
                            ->label(new HtmlString('Eu aceito os <a href="/termos-inscricao" target="_blank" class="text-yellow-500">Termos de Inscrição</a>'))
                            ->columnSpanFull(),
                        Checkbox::make('form_data.aceitar_politica_privacidade')
                            ->label(new HtmlString('Eu aceito a <a href="/politica-privacidade" target="_blank" class="text-yellow-500">Politica de Privacidade</a>'))
                            ->columnSpanFull(),
                    ]),


            ]);
    }

    public function submitForm(): void
    {
        $this->validate();
        if (!$this->data['form_data']['declaro']) {
            Notification::make()
                ->title('Inscrição não permitida')
                ->body('A inscrição não pode ser registrada, pois você já participou do Trekking antes.')
                ->duration(60000)
                ->danger()
                ->send();
            return;
        }
        if (
            !array_key_exists('aceite_termo_inscricao', $this->data['form_data']) ||
            !$this->data['form_data']['aceite_termo_inscricao'] ||
            !array_key_exists('aceitar_politica_privacidade', $this->data['form_data']) ||
            !$this->data['form_data']['aceitar_politica_privacidade']
        ) {

            Notification::make()
                ->title('Inscrição não permitida')
                ->body(new HtmlString('Para poder realizar a inscrição, é necessário ler e aceitar os
                    <a href="/termos-inscricao" target="_blank" class="text-yellow-500">Termos de Inscrição</a> e a nossa
                    <a href="/politica-privacidade" target="_blank" class="text-yellow-500">Política de Privacidade!</a>'))
                ->duration(60000)
                ->danger()
                ->send();

            return;
        }
        try {

            $this->data = Arr::only($this->data, ['nome', 'avatar_url', 'form_data']);

            $this->data['avatar_url'] = $this->data['avatar_url'][array_key_first($this->data['avatar_url'])]->store('foto-formulario', 'public');

            $campista = Campista::create($this->data);

            $this->comprado = true;
            $this->dispatch('inscricao-realizada');
            Notification::make()
                ->title('Registramos a sua inscrição')
                ->body('Já estamos com a sua ficha de inscrição, mas fique atento, para confirmar a sua presença leia atentamente as informações abaixo sobre o pagamento.')
                ->duration(60000)
                ->success()
                ->send();
            $this->reset(['data']);


            $recipient = User::all();

            Notification::make()
                ->info()
                ->title('Nova inscrição')
                ->body(new HtmlString('Uma nova inscrição foi enviada, para o(a) campista: <strong>' . strtoupper($campista->nome) . '</strong> acesse as inscrições para mais detalhes.'))
                ->sendToDatabase($recipient);

        } catch (\Exception $exception) {
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


}
