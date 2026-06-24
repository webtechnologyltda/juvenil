<?php

namespace App\Filament\Resources\EquipeTrabalhoResource;

use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\TipoEquipeTrabalho;
use App\Models\EquipeTrabalho as EquipeTrabalhoModel;
use Carbon\Carbon;
use Filament\Actions\Action as FormAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use JeffersonGoncalves\Filament\CepField\Forms\Components\CepInput;

abstract class EquipeTrabalhoForm
{
    public static function getFormCreate(): array
    {
        return [
            ...self::getFormEquipeTrabalho(),
        ];
    }

    public static function getAdminFormCreate(): array
    {
        return [
            Section::make('Dados básicos')
                ->icon('phosphor-user-list-fill')
                ->columns([
                    'default' => 1,
                    'lg' => 2,
                ])
                ->schema([
                    TextInput::make('nome')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('descricao')
                        ->label('Equipe')
                        ->placeholder('Ex.: Cozinha, Missão, Ordem e Limpeza')
                        ->datalist(fn (): array => self::registeredEquipeNames())
                        ->maxLength(255),

                    self::tipoEquipeField(),

                    Select::make('status')
                        ->label('Status da Inscrição')
                        ->searchable()
                        ->preload()
                        ->default(StatusInscricaoEquipeTrabalho::Aprovado->value)
                        ->options(StatusInscricaoEquipeTrabalho::class),

                    Select::make('tribo_id')
                        ->relationship('tribo', 'cor')
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn (Model $record): string => $record->cor ?? 'Não há tribos cadastradas')
                        ->label('Tribo'),
                ]),
        ];
    }

    public static function getFormUpdate(): array
    {
        return self::getAdminFormUpdate();
    }

    public static function getAdminFormUpdate(): array
    {
        return [
            ...self::getFormInformacaoInscricao(),
            ...self::getFormEquipeTrabalho(requireDetails: false),
        ];
    }

    public static function getFormEquipeTrabalho(bool $requireDetails = true): array
    {
        return [
            Section::make('Dados de Inscrição')
                ->icon('phosphor-user-list-fill')
                ->columns([
                    'default' => 1,
                    'lg' => 5,
                ])
                ->schema([
                    ...self::getFormFoto($requireDetails),
                    TextInput::make('nome')
                        ->required()
                        ->columnSpan(['default' => 1, 'lg' => 4])
                        ->maxLength(255),

                    TextInput::make('data_form.data_nacimento')
                        ->required($requireDetails)
                        ->mask('99/99/9999')
                        ->placeholder('DD/MM/AAAA')
                        ->columnSpan(1)
                        ->label('Data de Nascimento'),

                    ToggleButtons::make('data_form.sexo')
                        ->required($requireDetails)
                        ->inline(true)
                        ->inlineLabel(false)
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 'full',
                        ])
                        ->colors([
                            'M' => Color::Blue,
                            'F' => Color::Pink,
                        ])
                        ->icons([
                            'M' => 'eos-male',
                            'F' => 'eos-female',
                        ])
                        ->options([
                            'M' => 'Masculino',
                            'F' => 'Feminino',
                        ])
                        ->label('Sexo'),

                    TextInput::make('data_form.rede_social')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2,
                        ])
                        ->placeholder('Instagram, Facebook, etc...')
                        ->label('Rede Social'),

                    TextInput::make('data_form.telefone')
                        ->mask('(99) 9 9999-9999')
                        ->required($requireDetails)
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2,
                        ])
                        ->prefixIcon('heroicon-o-phone')
                        ->label('Telefone'),

                    TextInput::make('data_form.reponsavel_nome')
                        ->required($requireDetails)
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ])
                        ->label('Nome do Responsável Fora do Acampamento'),

                    TextInput::make('data_form.reponsavel_telefone')
                        ->required($requireDetails)
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
                            Html::make(new HtmlString('<p class="text-sm text-primary-600">Informe o CEP para preencher os campos de endereço automaticamente. Clique na lupa para localizar o endereço.</p>'))
                                ->columnSpanFull(),

                            CepInput::make('data_form.cep')
                                ->label('CEP')
                                ->required($requireDetails)
                                ->columnSpan([
                                    'default' => 1,
                                    'lg' => 1,
                                ])
                                ->setMode('suffix')
                                ->setActionLabel('Buscar CEP')
                                ->setActionLabelHidden(true)
                                ->setErrorMessage('CEP inválido.')
                                ->setStreetField('data_form.rua')
                                ->setNeighborhoodField('data_form.bairro')
                                ->setCityField('data_form.cidade')
                                ->setStateField('data_form.estado'),

                            TextInput::make('data_form.rua')
                                ->required($requireDetails)
                                ->columnSpan([
                                    'default' => 1,
                                    'lg' => 4,
                                ])
                                ->label('Rua'),

                            TextInput::make('data_form.numero')
                                ->required($requireDetails)
                                ->numeric()
                                ->columnSpan([
                                    'default' => 1,
                                    'lg' => 1,
                                ])
                                ->label('Número'),
                            TextInput::make('data_form.ponto_referencia')
                                ->label('Complemento')
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                    'xl' => 1,
                                ]),

                            TextInput::make('data_form.bairro')
                                ->required($requireDetails)
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 2,
                                    'xl' => 2,
                                ])
                                ->label('Bairro'),

                            TextInput::make('data_form.cidade')
                                ->required($requireDetails)
                                ->columnSpan([
                                    'default' => 1,
                                    'sm' => 1,
                                    'md' => 1,
                                    'xl' => 1,
                                ])
                                ->readOnly()
                                ->label('Cidade'),

                            TextInput::make('data_form.estado')
                                ->required($requireDetails)
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
                        ->required($requireDetails)
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
                        ->required(fn (Get $get): bool => $requireDetails && (bool) $get('data_form.ja_participou_retiro'))
                        ->visible(fn (Get $get) => $get('data_form.ja_participou_retiro') ?? false),

                    ToggleButtons::make('data_form.pode_missas_diarias')
                        ->label('Pode participar das missas diárias ?')
                        ->columnSpanFull()
                        ->required($requireDetails)
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
                        ->required($requireDetails),

                    TextInput::make('data_form.tamanho_camiseta_outro')
                        ->label('Tamanho da camiseta:')
                        ->extraInputAttributes([
                            'class' => 'uppercase',
                        ])
                        ->columnSpan(1)
                        ->required(fn (Get $get): bool => $requireDetails && $get('data_form.tamanho_camiseta') === 'O')
                        ->visible(fn (Get $get) => $get('data_form.tamanho_camiseta') == 'O')
                        ->minLength(1)
                        ->maxLength(3),

                    ToggleButtons::make('data_form.servir_no_acampamento')
                        ->label('Pode servir lá dentro do acampamento ?')
                        ->columnSpanFull()
                        ->required($requireDetails)
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
                ]),

        ];
    }

    public static function getFormFoto(bool $required = true): array
    {
        return [

            FileUpload::make('avatar_url')
                ->hiddenLabel()
                ->label('Foto de identificação')
                ->placeholder(fn () => new HtmlString('<span><a class="text-primary-600 font-bold">Clique aqui</a></br>Para adicionar uma foto sua</span>'))
                ->alignCenter()
                ->disk('public')
                ->image()
                ->directory('foto-formulario-equipe-trabalho')
                ->columnStart([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ])
                ->rules(['mimes:jpg,jpeg,png,webp'])
                ->loadingIndicatorPosition('center')
                ->panelAspectRatio('1:1')
                ->itemPanelAspectRatio('1:1')
                ->removeUploadedFileButtonPosition('top-center')
                ->uploadProgressIndicatorPosition('center')
                ->imageAspectRatio('1:1')
                ->automaticallyCropImagesToAspectRatio()
                ->automaticallyResizeImagesMode('cover')
                ->automaticallyResizeImagesToWidth('500')
                ->automaticallyResizeImagesToHeight('500')
                ->automaticallyUpscaleImagesWhenResizing(false)
                ->extraAttributes(['class' => 'juvenil-photo-upload'])
                ->imagePreviewHeight('250')
                ->panelLayout('integrated')
                ->required($required),

            Actions::make([
                FormAction::make('star')
                    ->icon('heroicon-m-eye')
                    ->label('Visualizar foto')
                    ->requiresConfirmation()
                    ->visible(fn (string $operation, array $data) => $operation !== 'create')
                    ->url(fn (Model $record) => Storage::disk('public')->url($record->avatar_url), shouldOpenInNewTab: true),
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
                    'lg' => 2,
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
                        ->afterStateUpdated(fn (Set $set) => $set('dia_pagamento', Carbon::now()->format('Y-m-d')))
                        ->options(StatusInscricaoEquipeTrabalho::class),

                    Select::make('tribo_id')
                        ->relationship('tribo', 'cor')
                        ->searchable()
                        ->preload()
                        ->getOptionLabelFromRecordUsing(fn (Model $record): string => $record->cor ?? 'Não há tribos cadastradas')
                        ->label('Tribo')
                        ->columnSpan([
                            'default' => 1,
                        ]),

                    TextInput::make('descricao')
                        ->label('Equipe')
                        ->placeholder('Ex.: Cozinha, Missão, Ordem e Limpeza')
                        ->datalist(fn (): array => self::registeredEquipeNames())
                        ->maxLength(255)
                        ->columnSpan([
                            'default' => 1,
                        ]),

                    self::tipoEquipeField()
                        ->columnSpan([
                            'default' => 1,
                        ]),
                ]),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function registeredEquipeNames(): array
    {
        return EquipeTrabalhoModel::query()
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->distinct()
            ->orderBy('descricao')
            ->pluck('descricao')
            ->all();
    }

    private static function tipoEquipeField(): ToggleButtons
    {
        return ToggleButtons::make('tipo_equipe')
            ->label('Tipo da equipe')
            ->options(TipoEquipeTrabalho::class)
            ->default(TipoEquipeTrabalho::Interna->value)
            ->inline()
            ->required();
    }
}
