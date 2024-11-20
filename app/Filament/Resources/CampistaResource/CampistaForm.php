<?php

namespace App\Filament\Resources\CampistaResource;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Leandrocfe\FilamentPtbrFormFields\Cep;

abstract class CampistaForm
{

    public static function getFormCreate(): array
    {
        return [
            Step::make('Dados Pessoais')
                ->icon('phosphor-user-list-fill')
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 5,
                ])
                ->schema([
                    ...self::getFormFoto(),
                    Grid::make()
                        ->columnSpanFull()
                        ->columns([
                            'default' => 1,
                            'lg' => 4,
                        ])
                        ->schema([
                            ...self::getFormDadosPessoais(),
                        ]),
                ]),
            Step::make('Dados dos Responsáveis')
                ->icon('ri-parent-fill')
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 4,
                ])
                ->schema([
                    ...self::getFormDadosResponsavel(),
                ]),
            Step::make('Endereço')
                ->icon('iconpark-local')
                ->columns([
                    'sm' => 1,
                    'lg' => 5,
                ])
                ->schema([
                    ...self::getFormEndereco(),
                ]),
            Step::make('Informações importantes')
                ->icon('fluentui-important-12')
                ->columns(5)
                ->schema([
                    ...self::getFormInformacoesImportantes()
                ]),
        ];
    }

    public static function getFormUpdate(): array
    {
        return [
            ...self::getFormInformacaoInscricao(),
            Tabs::make()
                ->columnSpanFull()
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Foto de Identificação')
                        ->icon('phosphor-identification-badge')
                        ->columns([
                            'default' => 1,
                            'lg' => 5,
                        ])
                        ->schema([
                            ...self::getFormFoto(),
                        ]),
                    Tab::make('Dados Pessoais')
                        ->icon('phosphor-user-list-fill')
                        ->columnSpanFull()
                        ->columns([
                            'default' => 1,
                            'lg' => 4,
                        ])
                        ->schema([
                            ...self::getFormDadosPessoais(),
                        ]),
                    Tab::make('Dados dos Responsáveis')
                        ->icon('ri-parent-fill')
                        ->columnSpanFull()
                        ->columns([
                            'default' => 1,
                            'lg' => 4,
                        ])
                        ->schema([
                            ...self::getFormDadosResponsavel(),
                        ]),
                    Tab::make('Endereço')
                        ->icon('iconpark-local')
                        ->columns([
                            'sm' => 1,
                            'lg' => 5,
                        ])
                        ->schema([
                            ...self::getFormEndereco(),
                        ]),
                    Tab::make('Informações importantes')
                        ->icon('fluentui-important-12')
                        ->columns(5)
                        ->schema([
                            ...self::getFormInformacoesImportantes()
                        ]),
                ])
        ];
    }

    public static function getFormView(): array
    {
        return [
            ...self::getFormInformacaoInscricao(),
            Section::make('Foto de Identificação')
                ->icon('phosphor-identification-badge')
                ->columns([
                    'default' => 1,
                    'lg' => 5,
                ])
                ->schema([
                    ...self::getFormFoto(),
                ]),
            Section::make('Dados Pessoais')
                ->icon('phosphor-user-list-fill')
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 4,
                ])
                ->schema([
                    ...self::getFormDadosPessoais(),
                ]),
            Section::make('Dados dos Responsáveis')
                ->icon('ri-parent-fill')
                ->columnSpanFull()
                ->columns([
                    'default' => 1,
                    'lg' => 4,
                ])
                ->schema([
                    ...self::getFormDadosResponsavel(),
                ]),
            Section::make('Endereço')
                ->icon('iconpark-local')
                ->columns([
                    'sm' => 1,
                    'lg' => 5,
                ])
                ->schema([
                    ...self::getFormEndereco(),
                ]),
            Section::make('Informações importantes')
                ->icon('fluentui-important-12')
                ->columns(5)
                ->schema([
                    ...self::getFormInformacoesImportantes()
                ]),
        ];
    }

    public static function getFormFoto(): array
    {
        return [

            FileUpload::make('avatar_url')
                ->hiddenLabel()
                ->label('Foto de identificação')
                ->optimize('webp')
                ->placeholder(fn() => new HtmlString('<span><a class="text-primary-600 font-bold">Clique aqui</a></br>Para adicionar uma foto sua</span>'))
                ->resize(15)
                ->alignCenter()
                ->imageEditor()
                ->directory('foto-formulario')
                ->imagePreviewHeight('250')
                ->previewable(true)
                ->columnSpan(1)
                ->columnStart([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->imageCropAspectRatio('1:1')
                ->loadingIndicatorPosition('center')
                ->panelAspectRatio('1:1')
                ->removeUploadedFileButtonPosition('top-center')
                ->uploadButtonPosition('center')
                ->uploadProgressIndicatorPosition('center')
                ->imageEditorMode(2)
                ->panelLayout('integrated')
                ->imageEditorEmptyFillColor('#000000')
                ->required(),

            Actions::make([
                FormAction::make('star')
                    ->icon('heroicon-m-eye')
                    ->label('Visualizar foto')
                    ->requiresConfirmation()
                    ->visible(fn(string $operation, array $data) => $operation !== 'create')
                    ->url(fn(Model $record) => Storage::url($record->avatar_url), shouldOpenInNewTab: true),
            ])
                ->alignCenter()
                ->columnSpanFull(),
        ];
    }

    public static function getFormDadosPessoais(): array
    {
        return [

            TextInput::make('nome')
                ->required()
                ->columnSpan(['default' => 1, 'lg' => 2])
                ->maxLength(255),
            TextInput::make('form_data.data_nacimento')
                ->required()
                ->mask('99/99/9999')
                ->columnSpan(1)
                ->label('Data de Nascimento'),

            Radio::make('form_data.sexo')
                ->required()
                ->inline()
                ->inlineLabel(false)
                ->columnSpan(1)
                ->options([
                    'M' => 'Masculino',
                    'F' => 'Feminino',
                ])
                ->label('Sexo'),

            TextInput::make('form_data.altura')
                ->integer()
                ->columnSpan(1)
                ->suffix('cm')
                ->label('Altura'),

            TextInput::make('form_data.peso')
                ->integer()
                ->columnSpan(1)
                ->suffix('kg')
                ->label('Peso'),

            TextInput::make('form_data.rede_social')
                ->columnSpan([
                    'default' => 1,
                    'lg' => 1,
                ])
                ->placeholder('Instagram, Facebook, etc...')
                ->label('Rede Social'),

            TextInput::make('form_data.telefone_campista')
                ->mask('(99) 9 9999-9999')
                ->required()
                ->columnSpan(1)
                ->label('Telefone Campista'),
        ];
    }

    public static function getFormDadosResponsavel(): array
    {
        return [
            TextInput::make('form_data.telefone_reponsavel_1')
                ->required()
                ->mask('(99) 9 9999-9999')
                ->columnSpan(1)
                ->label('Telefone do responsável para contato'),

            TextInput::make('form_data.telefone_reponsavel_nome_1')
                ->required()
                ->columnSpan(1)
                ->label('Nome do contato'),

            TextInput::make('form_data.telefone_reponsavel_2')
                ->required()
                ->mask('(99) 9 9999-9999')
                ->columnSpan(1)
                ->label('Telefone do responsável para contato'),

            TextInput::make('form_data.telefone_reponsavel_nome_2')
                ->required()
                ->columnSpan(1)
                ->label('Nome do contato'),
        ];
    }

    public static function getFormEndereco(): array
    {
        return [

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
                    'lg' => 1,
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
                    'lg' => 4,
                ])
                ->label('Rua'),

            TextInput::make('form_data.numero')
                ->required()
                ->numeric()
                ->columnSpan([
                    'default' => 1,
                    'lg' => 1,
                ])
                ->label('Número'),
            TextInput::make('form_data.ponto_referencia')
                ->label('Ponto Referência')
                ->columnSpan([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 1,
                    'xl' => 1,
                ]),

            TextInput::make('form_data.bairro')
                ->required()
                ->columnSpan([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 2,
                    'xl' => 2,
                ])
                ->label('Bairro'),

            TextInput::make('form_data.cidade')
                ->required()
                ->columnSpan([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 1,
                    'xl' => 1,
                ])
                ->readOnly()
                ->label('Cidade'),

            TextInput::make('form_data.estado')
                ->required()
                ->columnSpan([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 1,
                    'xl' => 1,
                ])
                ->readOnly()
                ->label('Estado'),
        ];
    }

    public static function getFormTribo(): array
    {
        return [
            Select::make('tribo_id')
                ->relationship('tribo', 'cor')
                ->searchable()
                ->preload()
                ->getOptionLabelFromRecordUsing(fn(Model $record) => $record->cor ?? 'Não há tribos cadastradas')
                ->columnSpan([
                    'default' => 1,
                ]),
        ];
    }

    public static function getFormInformacoesImportantes(): array
    {
        return [
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
                ->columnSpanFull()
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
                ->columnSpan(1)
                ->required()
                ->visible(fn(Get $get) => $get('form_data.tamanho_camiseta') == 'O')
                ->requiredIf('tamanho_camiseta', fn(Get $get) => $get('form_data.tamanho_camiseta') == 'O')
                ->minLength(1)
                ->maxLength(3),

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
            Checkbox::make('form_data.aceite_termo_inscricao')
                ->label(new HtmlString('Eu aceito os <a href="/termos-inscricao" target="_blank" class="text-primary-500 font-bold">Termos de Inscrição</a>'))
                ->required()
                ->columnSpanFull(),
            Checkbox::make('form_data.aceitar_politica_privacidade')
                ->label(new HtmlString('Eu aceito a <a href="/politica-privacidade" target="_blank" class="text-primary-500 font-bold">Politica de Privacidade</a>'))
                ->required()
                ->columnSpanFull(),
        ];
    }

    public static function getFormInformacaoInscricao(): array
    {
        return [
            Grid::make([
                'default' => 1,
                'lg' => 3,
            ])
                ->schema([
                    Section::make('Informação da Inscrição')
                        ->icon('heroicon-o-information-circle')
                        ->columns([
                            'sm' => 2,
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2
                        ])
                        ->schema([
                            Select::make('status')
                                ->columnSpan([
                                    'default' => 1,
                                ])
                                ->searchable()
                                ->label('Status da Inscrição')
                                ->live()
                                ->preload()
                                ->afterStateUpdated(fn(Set $set) => $set('dia_pagamento', Carbon::now()->format('Y-m-d')))
                                ->options(StatusInscricao::class),



                            Select::make('forma_pagamento')
                                ->columnSpan([
                                    'default' => 1,
                                ])
                                ->searchable()
                                ->preload()
                                ->visible(fn(Get $get) => $get('status') == StatusInscricao::Pago->value)
                                ->required(fn(Get $get) => $get('status') == StatusInscricao::Pago->value)
                                ->options(FormaPagamento::class),

                            DatePicker::make('dia_pagamento')
                                ->label('Data de Pagamento')
                                ->columnSpan([
                                    'default' => 1,
                                ]),

                            ...self::getFormTribo(),

                            Textarea::make('observacoes')
                                ->label('Observações')
                                ->rows(3)
                                ->placeholder('Observações sobre a inscricão.')
                                ->columnSpan([
                                    'default' => 1,
                                ]),
                            Hidden::make('space_forma_pagamento')
                                ->columnSpan([
                                    'default' => 1,
                                ])
                                ->hidden(fn(Get $get) => $get('status') == StatusInscricao::Pago->value),
                        ]),

                    Section::make()
                        ->label('Comprovantes')
                        ->icon('heroicon-o-document-text')
                        ->description('Anexe os comprovantes de pagamento.')
                        ->columnSpan(1)
                        ->schema([
                            TextInput::make('form_data.comprovante_nome')
                                ->label('Nome Comprovante'),
                            FileUpload::make('form_data.comprovante')
                                ->placeholder( 'Tamanho max.: 2MB')
                                ->hint('Tamanho máximo: 2MB')
                                ->label('Documento')
                                ->downloadable()
                                ->openable()
                                ->multiple()
                                ->maxSize(2048)
                                ->uploadingMessage('Carregando...')
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->previewable(true)
                                ->columnSpan(2),
                        ]),

                ])
        ];
    }


}
