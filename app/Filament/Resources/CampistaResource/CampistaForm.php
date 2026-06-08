<?php

namespace App\Filament\Resources\CampistaResource;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\LancamentoItem;
use Carbon\Carbon;
use Filament\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use JeffersonGoncalves\Filament\CepField\Forms\Components\CepInput;

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
                    ...self::getFormInformacoesImportantes(),
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
                            ...self::getFormInformacoesImportantes(),
                        ]),
                ]),
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
                    ...self::getFormInformacoesImportantes(),
                ]),
        ];
    }

    public static function getFormFoto(): array
    {
        return [

            FileUpload::make('avatar_url')
                ->hiddenLabel()
                ->label('Foto de identificação')
                ->placeholder(fn () => new HtmlString('<span><a class="text-primary-600 font-bold">Clique aqui</a></br>Para adicionar uma foto sua</span>'))
                ->alignCenter()
                ->disk('public')
                ->image()
                ->acceptedFileTypes([
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                ])
                ->rules(['mimes:jpg,jpeg,png,webp'])
                ->directory('foto-formulario')
                ->imagePreviewHeight('250')
                ->previewable(true)
                ->columnSpan(1)
                ->columnStart([
                    'default' => 1,
                    'lg' => 3,
                ])
                ->loadingIndicatorPosition('center')
                ->panelAspectRatio('1:1')
                ->itemPanelAspectRatio('1:1')
                ->removeUploadedFileButtonPosition('top-center')
                ->uploadButtonPosition('center')
                ->uploadProgressIndicatorPosition('center')
                ->panelLayout('integrated')
                ->imageAspectRatio('1:1')
                ->automaticallyCropImagesToAspectRatio()
                ->automaticallyResizeImagesMode('cover')
                ->automaticallyResizeImagesToWidth('500')
                ->automaticallyResizeImagesToHeight('500')
                ->automaticallyUpscaleImagesWhenResizing(false)
                ->extraAttributes(['class' => 'juvenil-photo-upload'])
                ->validationMessages([
                    'mimetypes' => 'Envie uma imagem nos formatos JPG, JPEG, PNG ou WEBP.',
                    'mimes' => 'Envie uma imagem nos formatos JPG, JPEG, PNG ou WEBP.',
                ])
                ->required(),

            Actions::make([
                FormAction::make('star')
                    ->icon('heroicon-m-eye')
                    ->label('Visualizar foto')
                    ->requiresConfirmation()
                    ->visible(fn (string $operation, array $data) => $operation !== 'create')
                    ->url(fn (Model $record) => Storage::disk('public')->url($record->avatar_url), shouldOpenInNewTab: true),
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

            ToggleButtons::make('form_data.sexo')
                ->required()
                ->inline()
                ->inlineLabel(false)
                ->columnSpan(1)
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
                ->label('Telefone Responsável'),

            TextInput::make('form_data.telefone_reponsavel_nome_1')
                ->required()
                ->columnSpan(1)
                ->label('Nome do Contato'),
        ];
    }

    public static function getFormEndereco(): array
    {
        return [

            Html::make(new HtmlString('<p class="text-sm text-primary-600">Informe o CEP para preencher os campos de endereço automaticamente. Clique na lupa para localizar o endereço.</p>'))
                ->columnSpanFull(),

            CepInput::make('form_data.cep')
                ->label('CEP')
                ->required()
                ->columnSpan([
                    'default' => 1,
                    'lg' => 1,
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
                ->label('Complemento')
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
                ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->cor ?? 'Não há tribos cadastradas')
                ->columnSpan([
                    'default' => 1,
                ]),
        ];
    }

    public static function getFormInformacoesImportantes(): array
    {
        return [
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
                ->required(fn (Get $get) => self::canViewSensitiveHealth() && $get('form_data.toma_remedio') == true)
                ->visible(fn (Get $get) => self::canViewSensitiveHealth() && $get('form_data.toma_remedio') == true)
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
                ->required(fn (Get $get) => self::canViewSensitiveHealth() && $get('form_data.tem_recomendacao') == true)
                ->visible(fn (Get $get) => self::canViewSensitiveHealth() && $get('form_data.tem_recomendacao') == true)
                ->rows(3)
                ->columnSpanFull(),

            ToggleButtons::make('form_data.tamanho_camiseta')
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
                ->visible(fn (Get $get) => $get('form_data.tamanho_camiseta') == 'O')
                ->requiredIf('tamanho_camiseta', fn (Get $get) => $get('form_data.tamanho_camiseta') == 'O')
                ->minLength(1)
                ->maxLength(3),

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

    private static function selectedParishIs(Get $get, int $parish): bool
    {
        $selectedParish = $get('form_data.paroquia');

        return $selectedParish !== null
            && $selectedParish !== ''
            && (int) $selectedParish === $parish;
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
                                ->options(StatusInscricao::class),

                            ...self::getFormTribo(),

                            Textarea::make('observacoes')
                                ->label('Observações')
                                ->rows(3)
                                ->placeholder('Observações sobre a inscricão.')
                                ->columnSpan([
                                    'default' => 1,
                                ]),
                        ]),

                    Section::make('Pagamentos vinculados')
                        ->icon('heroicon-o-banknotes')
                        ->description('Dados gerados pelos lançamentos financeiros vinculados a esta inscrição.')
                        ->columnSpan(1)
                        ->schema([
                            Html::make(fn (?Campista $record): HtmlString => self::paymentSummaryHtml($record))
                                ->columnSpanFull(),
                        ]),

                ]),
        ];
    }

    public static function paymentSummaryHtml(?Campista $record): HtmlString
    {
        if (! $record?->exists) {
            return new HtmlString(self::paymentSummaryEmptyHtml('Salve a inscrição para vincular lançamentos financeiros.'));
        }

        $payments = $record->lancamentoItems()
            ->with('lancamento')
            ->orderByDesc('id')
            ->get();

        if ($payments->isEmpty()) {
            return new HtmlString(self::paymentSummaryEmptyHtml('Nenhum lançamento financeiro vinculado a esta inscrição.'));
        }

        $items = $payments
            ->map(fn (LancamentoItem $payment): string => self::paymentSummaryItemHtml($payment))
            ->implode('');

        return new HtmlString('<div style="display:grid;gap:.75rem;">'.$items.'</div>');
    }

    private static function paymentSummaryEmptyHtml(string $message): string
    {
        return '<div style="border:1px solid rgba(157,219,239,.22);background:rgba(3,24,28,.42);padding:1rem;color:#d8f2fa;font-size:.95rem;">'
            .e($message)
            .'</div>';
    }

    private static function paymentSummaryItemHtml(LancamentoItem $payment): string
    {
        $lancamento = $payment->lancamento;
        $status = $lancamento?->status?->getLabel() ?? 'Sem status';
        $method = $lancamento?->forma_pagamento?->getLabel() ?? 'Sem forma';
        $date = $lancamento?->data ? Carbon::parse($lancamento->data)->format('d/m/Y') : 'Sem data';
        $name = $lancamento?->nome ?? 'Lançamento removido';

        return '<article style="border:1px solid rgba(157,219,239,.24);background:rgba(4,31,35,.78);padding:1rem;">'
            .'<p style="margin:0 0 .35rem;color:#9ddbef;font-size:.72rem;font-weight:900;letter-spacing:.16em;text-transform:uppercase;">Lançamento financeiro</p>'
            .'<strong style="display:block;color:#f4fbfd;font-size:1rem;line-height:1.25;">'.e($name).'</strong>'
            .'<dl style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem;margin:.85rem 0 0;">'
            .self::paymentSummaryFieldHtml('Valor aplicado', self::money((int) $payment->valor))
            .self::paymentSummaryFieldHtml('Data', $date)
            .self::paymentSummaryFieldHtml('Forma', $method)
            .self::paymentSummaryFieldHtml('Status', $status)
            .'</dl>'
            .'</article>';
    }

    private static function paymentSummaryFieldHtml(string $label, string $value): string
    {
        return '<div>'
            .'<dt style="color:rgba(216,242,250,.64);font-size:.68rem;font-weight:900;letter-spacing:.12em;text-transform:uppercase;">'.e($label).'</dt>'
            .'<dd style="margin:.2rem 0 0;color:#f4fbfd;font-size:.9rem;font-weight:800;">'.e($value).'</dd>'
            .'</div>';
    }

    private static function money(int $amount): string
    {
        return 'R$ '.number_format($amount / 100, 2, ',', '.');
    }

    public static function canViewSensitiveHealth(): bool
    {
        return auth()->user()?->can('view_sensitive_health_campista') ?? false;
    }

    public static function redactSensitiveHealthDetails(array $data): array
    {
        if (self::canViewSensitiveHealth()) {
            return $data;
        }

        unset($data['form_data']['remedio'], $data['form_data']['recomendacao']);

        return $data;
    }

    public static function preserveSensitiveHealthDetails(array $data, Model $record): array
    {
        if (self::canViewSensitiveHealth()) {
            return $data;
        }

        foreach (['remedio', 'recomendacao'] as $key) {
            if (array_key_exists($key, $record->form_data ?? [])) {
                $data['form_data'][$key] = $record->form_data[$key];
            }
        }

        return $data;
    }
}
