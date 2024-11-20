<?php

namespace App\Filament\Resources\EquipeTrabalhoResource;

use App\Enums\StatusInscricaoEquipeTrabalho;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Leandrocfe\FilamentPtbrFormFields\Cep;

abstract class EquipeTrabalhoForm
{
    public static function getFormCreate(): array
    {
        return [
            ...self::getFormEquipeTrabalho()
        ];
    }

    public static function getFormUpdate(): array
    {
        return [
            ...self::getFormInformacaoInscricao(),
            ...self::getFormEquipeTrabalho()
        ];
    }

    public static function getFormEquipeTrabalho(): array
    {
        return [
            Section::make('Dados de Inscrição')
                ->icon('phosphor-user-list-fill')
                ->columns([
                    'default' => 1,
                    'lg' => 5,
                ])
                ->schema([
                    ...self::getFormFoto(),
                    TextInput::make('nome')
                        ->required()
                        ->columnSpan(['default' => 1, 'lg' => 4])
                        ->maxLength(255),

                    TextInput::make('data_form.data_nacimento')
                        ->required()
                        ->mask('99/99/9999')
                        ->placeholder('DD/MM/AAAA')
                        ->columnSpan(1)
                        ->label('Data de Nascimento'),

                    TextInput::make('data_form.rede_social')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2,
                        ])
                        ->placeholder('Instagram, Facebook, etc...')
                        ->label('Rede Social'),

                    TextInput::make('data_form.telefone')
                        ->mask('(99) 9 9999-9999')
                        ->required()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2,
                        ])
                        ->prefixIcon('heroicon-o-phone')
                        ->label('Telefone'),

                    TextInput::make('data_form.reponsavel_nome')
                        ->required()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ])
                        ->label('Nome do Responsável Fora do Acampamento'),

                    TextInput::make('data_form.reponsavel_telefone')
                        ->required()
                        ->mask('(99) 9 9999-9999')
                        ->prefixIcon('heroicon-o-phone')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2,
                        ])
                        ->label('Telefone do Responsável Fora do Acampamento'),

                    Fieldset::make()
                        ->label('Endereço')
                        ->columns([
                            'default' => 1,
                            'lg' => 5,
                        ])
                        ->schema([
                        Placeholder::make('info_endereco')
                            ->hint('Informe o CEP para preencher os campos de endereço automaticamente. Clique na lupa para localizar o endereço.')
                            ->hintColor(Color::Yellow)
                            ->hintIcon('heroicon-o-exclamation-circle')
                            ->hiddenLabel()
                            ->columnSpanFull(),

                        Cep::make('data_form.cep')
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
                                    'data_form.rua' => 'logradouro',
                                    'data_form.numero' => 'numero',
                                    'data_form.ponto_referencia' => 'complemento',
                                    'data_form.bairro' => 'bairro',
                                    'data_form.cidade' => 'localidade',
                                    'data_form.estado' => 'uf'
                                ],
                            ),

                        TextInput::make('data_form.rua')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 4,
                            ])
                            ->label('Rua'),

                        TextInput::make('data_form.numero')
                            ->required()
                            ->numeric()
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 1,
                            ])
                            ->label('Número'),
                        TextInput::make('data_form.ponto_referencia')
                            ->label('Ponto Referência')
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                                'xl' => 1,
                            ]),

                        TextInput::make('data_form.bairro')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 2,
                                'xl' => 2,
                            ])
                            ->label('Bairro'),

                        TextInput::make('data_form.cidade')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                                'xl' => 1,
                            ])
                            ->readOnly()
                            ->label('Cidade'),

                        TextInput::make('data_form.estado')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'sm' => 1,
                                'md' => 1,
                                'xl' => 1,
                            ])
                            ->readOnly()
                            ->label('Estado'),
                    ]),

                    ToggleButtons::make('data_form.ja_participou_retiro')
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

                    TagsInput::make('data_form.retiro_que_participou')
                        ->label('Qual?')
                        ->placeholder('Especifique o qual acampamento')
                        ->columnSpanFull()
                        ->live()
                        ->validationMessages([
                            'required' => 'Necessário informar ao menos um acampamento que já tenha participado.',
                        ])
                        ->required(fn(Get $get) => $get('data_form.ja_participou_retiro'))
                        ->visible(fn(Get $get) => $get('data_form.ja_participou_retiro') ?? false),

                    ToggleButtons::make('data_form.pode_missas_diarias')
                        ->label('Pode participar das missas diárias ?')
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

                    ToggleButtons::make('data_form.tamanho_camiseta')
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

                    TextInput::make('data_form.tamanho_camiseta_outro')
                        ->label('Tamanho da camiseta:')
                        ->extraInputAttributes([
                            'class' => 'uppercase'
                        ])
                        ->columnSpan(1)
                        ->required()
                        ->visible(fn(Get $get) => $get('data_form.tamanho_camiseta') == 'O')
                        ->requiredIf('tamanho_camiseta', fn(Get $get) => $get('data_form.tamanho_camiseta') == 'O')
                        ->minLength(1)
                        ->maxLength(3),

                    ToggleButtons::make('data_form.servir_no_acampamento')
                        ->label('Pode servir lá dentro do acampamento ?')
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
                ])

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
                ->directory('foto-formulario-equipe-trabalho')
                ->columnStart([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->acceptedFileTypes(['image/png', 'image/jpg', 'image/jpeg', 'image/webp'])
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

            Actions::make([
                FormAction::make('star')
                    ->icon('heroicon-m-eye')
                    ->label('Visualizar foto')
                    ->requiresConfirmation()
                    ->visible(fn(string $operation, array $data) => $operation !== 'create')
                    ->url(fn(Model $record) => Storage::url($record->avatar_url), shouldOpenInNewTab: true),
            ])
                ->visibleOn(['edit', 'view'])
                ->alignCenter()
                ->columnSpanFull(),
        ];
    }

    public static function getFormInformacaoInscricao(): array
    {
        return [
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
                        ->options(StatusInscricaoEquipeTrabalho::class),

                    Textarea::make('observacoes')
                        ->label('Observações')
                        ->rows(3)
                        ->placeholder('Observações sobre a inscricão.')
                        ->columnSpan([
                            'default' => 1,
                        ]),
                ]),
        ];
    }
}
