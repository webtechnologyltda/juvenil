<?php

namespace App\Filament\Pages;

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Settings\GeneralSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use DateTimeInterface;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
use Illuminate\Support\Carbon;
use Leandrocfe\FilamentPtbrFormFields\Money;
use Leandrocfe\FilamentPtbrFormFields\PhoneNumber;

class GeneralSettingsPage extends SettingsPage
{
    use HasPageShield;

    protected static ?string $title = 'Configurações Gerais';

    protected static ?int $navigationSort = 99;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static string $settings = GeneralSettings::class;

    private const DATE_TIME_FIELDS = [
        'data_inicio_inscricoes',
        'data_final_inscricoes',
    ];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'lg' => 12,
                        'xl' => 12,
                    ])
                    ->schema([
                        Section::make('Inscrições dos Campistas')
                            ->description('Controle principal da inscrição pública dos campistas.')
                            ->schema([
                                Select::make('liberacao_inscricoes_status')
                                    ->label('Status das Inscrições Campistas')
                                    ->options(LiberacaoInscricoesStatusEnum::configurationOptions())
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->prefixIcon(fn ($state) => self::resolveCampistaStatus($state)?->getIcon())
                                    ->prefixIconColor(fn ($state) => self::resolveCampistaStatus($state)?->getColor())
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => '6',
                                'xl' => '6',
                            ])
                            ->columns(12)
                            ->icon('heroicon-o-user-group'),

                        Section::make('Equipe de Trabalho')
                            ->description('Liberação separada para inscrições da equipe.')
                            ->schema([
                                Select::make('liberacao_inscricoes_equipe_trabalho_status')
                                    ->label('Status das Inscrições Equipe de Trabalho')
                                    ->options(LiberacaoInscricoesEquipeTrabalhoStatusEnum::configurationOptions())
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->prefixIcon(fn ($state) => self::resolveEquipeTrabalhoStatus($state)?->getIcon())
                                    ->prefixIconColor(fn ($state) => self::resolveEquipeTrabalhoStatus($state)?->getColor())
                                    ->columnSpanFull(),

                                Money::make('valor_equipe_trabalho_interna')
                                    ->label('Valor equipe interna')
                                    ->intFormat()
                                    ->prefix(RawJs::make('R$'))
                                    ->helperText('Use 0 para bloquear lançamentos vinculados a equipes internas até configurar o valor.')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                    ]),

                                Money::make('valor_equipe_trabalho_externa')
                                    ->label('Valor equipe externa')
                                    ->intFormat()
                                    ->prefix(RawJs::make('R$'))
                                    ->helperText('Use 0 para bloquear lançamentos vinculados a equipes externas até configurar o valor.')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                    ]),
                            ])
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => '6',
                                'xl' => '6',
                            ])
                            ->columns(12)
                            ->icon('heroicon-o-identification'),

                        Section::make('Capacidade e Faixa Etária')
                            ->description('Limites usados para validar novas inscrições.')
                            ->schema([
                                TextInput::make('qtd_max_vagas_masculino')
                                    ->label('Limite de Campistas Homens')
                                    ->integer()
                                    ->minValue(0)
                                    ->suffix('homens')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                        'lg' => '3',
                                    ]),

                                TextInput::make('qtd_max_vagas_feminino')
                                    ->label('Limite de Campistas Mulheres')
                                    ->integer()
                                    ->minValue(0)
                                    ->suffix('mulheres')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                        'lg' => '3',
                                    ]),

                                TextInput::make('idade_minima')
                                    ->label('Idade mínima')
                                    ->integer()
                                    ->minValue(0)
                                    ->suffix('anos')
                                    ->helperText('Use 0 para liberar inscrições de qualquer idade nesse limite.')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                        'lg' => '3',
                                    ]),

                                TextInput::make('idade_maxima')
                                    ->label('Idade máxima')
                                    ->integer()
                                    ->minValue(0)
                                    ->suffix('anos')
                                    ->helperText('Use 0 para liberar inscrições de qualquer idade nesse limite.')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                        'lg' => '3',
                                    ]),

                            ])
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => '8',
                                'xl' => '8',
                            ])
                            ->columns(12)
                            ->icon('heroicon-o-adjustments-horizontal'),

                        Section::make('Período de Inscrição')
                            ->description('Janela exibida e aplicada ao formulário público.')
                            ->schema([
                                DateTimePicker::make('data_inicio_inscricoes')
                                    ->label('Início das Inscrições')
                                    ->native(false)
                                    ->seconds(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->columnSpan([
                                        'default' => 'full',
                                    ]),

                                DateTimePicker::make('data_final_inscricoes')
                                    ->label('Fim das Inscrições')
                                    ->native(false)
                                    ->seconds(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->afterOrEqual('data_inicio_inscricoes')
                                    ->columnSpan([
                                        'default' => 'full',
                                    ]),
                            ])
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => '4',
                                'xl' => '4',
                            ])
                            ->columns(12)
                            ->icon('heroicon-o-calendar-days'),

                        Section::make('Pagamento PIX')
                            ->description('Informações financeiras exibidas na inscrição.')
                            ->schema([
                                Money::make('valor_acampamento')
                                    ->label('Valor do Acampamento')
                                    ->intFormat()
                                    ->prefix(RawJs::make('R$'))
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => '4',
                                    ]),

                                FileUpload::make('pix_qr_code')
                                    ->label('Imagem do QR Code PIX')
                                    ->disk('public')
                                    ->directory('settings/pix')
                                    ->visibility('public')
                                    ->image()
                                    ->acceptedFileTypes([
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                    ])
                                    ->rules(['mimes:jpg,jpeg,png,webp'])
                                    ->maxSize(2048)
                                    ->downloadable()
                                    ->openable()
                                    ->previewable(true)
                                    ->imageAspectRatio('1:1')
                                    ->automaticallyCropImagesToAspectRatio()
                                    ->automaticallyResizeImagesMode('cover')
                                    ->automaticallyResizeImagesToWidth('600')
                                    ->automaticallyResizeImagesToHeight('600')
                                    ->automaticallyUpscaleImagesWhenResizing(false)
                                    ->panelAspectRatio('1:1')
                                    ->itemPanelAspectRatio('1:1')
                                    ->panelLayout('integrated')
                                    ->imagePreviewHeight('180')
                                    ->extraAttributes(['class' => 'juvenil-pix-qr-upload'])
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => '8',
                                    ])
                                    ->validationMessages([
                                        'mimetypes' => 'Envie uma imagem nos formatos JPG, JPEG, PNG ou WEBP.',
                                        'mimes' => 'Envie uma imagem nos formatos JPG, JPEG, PNG ou WEBP.',
                                    ]),

                                Textarea::make('pix_copia_cola')
                                    ->label('PIX copia e cola')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => '8',
                                'xl' => '8',
                            ])
                            ->columns(12)
                            ->icon('fab-pix'),

                        Section::make('Atendimento e Documentos')
                            ->description('Canal de suporte e termo entregue ao campista.')
                            ->schema([
                                PhoneNumber::make('telefone_atendente')
                                    ->hidden()
                                    ->dehydrated(),

                                Repeater::make('atendentes')
                                    ->label('Atendentes')
                                    ->helperText('Cadastre até dois contatos para dúvidas, um para comprovantes e outros para necessidades específicas.')
                                    ->schema([
                                        Select::make('tipo')
                                            ->label('Finalidade')
                                            ->options([
                                                'duvidas' => 'Dúvidas',
                                                'comprovante' => 'Comprovante',
                                                'necessidade_especifica' => 'Necessidade específica',
                                            ])
                                            ->native(false)
                                            ->required()
                                            ->columnSpanFull(),

                                        TextInput::make('nome')
                                            ->label('Nome')
                                            ->required()
                                            ->maxLength(80)
                                            ->columnSpanFull(),

                                        PhoneNumber::make('telefone')
                                            ->label('Telefone')
                                            ->required()
                                            ->columnSpanFull(),

                                        Textarea::make('observacao')
                                            ->label('Observação')
                                            ->rows(2)
                                            ->maxLength(180)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(12)
                                    ->defaultItems(0)
                                    ->addActionLabel('Adicionar atendente')
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->itemLabel(fn (array $state): ?string => filled($state['nome'] ?? null)
                                        ? sprintf('%s - %s', $state['nome'], $state['telefone'] ?? 'sem telefone')
                                        : null)
                                    ->columnSpanFull(),

                                FileUpload::make('termo_responsabilidade')
                                    ->label('Documento do termo')
                                    ->helperText('Envie o termo que ficará disponível no botão público após a inscrição.')
                                    ->disk('public')
                                    ->directory('settings/termos')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    ])
                                    ->rules(['mimes:pdf,doc,docx'])
                                    ->maxSize(5120)
                                    ->downloadable()
                                    ->openable()
                                    ->previewable(false)
                                    ->columnSpanFull()
                                    ->validationMessages([
                                        'mimetypes' => 'Envie um documento nos formatos PDF, DOC ou DOCX.',
                                        'mimes' => 'Envie um documento nos formatos PDF, DOC ou DOCX.',
                                    ]),
                            ])
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => '4',
                                'xl' => '4',
                            ])
                            ->columns(12)
                            ->icon('heroicon-o-document-text'),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (self::DATE_TIME_FIELDS as $field) {
            $data[$field] = self::normalizeDateTimePickerState($data[$field] ?? null);
        }

        $data['atendentes'] = self::normalizeAttendanceContactsForFill(
            $data['atendentes'] ?? null,
            $data['telefone_atendente'] ?? null,
        );

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['liberacao_inscricoes_status'] = self::normalizeIntegerSettingValue(
            $data['liberacao_inscricoes_status'] ?? null,
        );
        $data['liberacao_inscricoes_equipe_trabalho_status'] = self::normalizeIntegerSettingValue(
            $data['liberacao_inscricoes_equipe_trabalho_status'] ?? null,
        );

        foreach (self::DATE_TIME_FIELDS as $field) {
            $data[$field] = self::normalizeDateTimeSettingValue($data[$field] ?? null);
        }

        $data['atendentes'] = self::normalizeAttendanceContacts($data['atendentes'] ?? null);

        return $data;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private static function normalizeAttendanceContactsForFill(mixed $contacts, mixed $legacyPhone): array
    {
        $contacts = self::normalizeAttendanceContacts($contacts);

        if ($contacts !== [] || blank($legacyPhone)) {
            return $contacts;
        }

        return [
            [
                'nome' => 'Atendente',
                'telefone' => (string) $legacyPhone,
                'tipo' => 'duvidas',
                'observacao' => null,
            ],
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private static function normalizeAttendanceContacts(mixed $contacts): array
    {
        if (! is_array($contacts)) {
            return [];
        }

        return collect($contacts)
            ->map(function (mixed $contact): ?array {
                if (! is_array($contact) || blank($contact['telefone'] ?? null)) {
                    return null;
                }

                return [
                    'nome' => filled($contact['nome'] ?? null) ? (string) $contact['nome'] : 'Atendente',
                    'telefone' => (string) $contact['telefone'],
                    'tipo' => in_array($contact['tipo'] ?? null, ['duvidas', 'comprovante', 'necessidade_especifica'], true)
                        ? (string) $contact['tipo']
                        : 'duvidas',
                    'observacao' => filled($contact['observacao'] ?? null) ? (string) $contact['observacao'] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private static function normalizeDateTimePickerState(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        if ($state instanceof DateTimeInterface) {
            return Carbon::instance($state)->format('Y-m-d H:i:s');
        }

        return (string) $state;
    }

    private static function normalizeDateTimeSettingValue(mixed $state): ?DateTimeInterface
    {
        if ($state === null || $state === '') {
            return null;
        }

        if ($state instanceof DateTimeInterface) {
            return $state;
        }

        return Carbon::parse((string) $state, config('app.timezone'));
    }

    private static function normalizeIntegerSettingValue(mixed $state): mixed
    {
        if ($state instanceof BackedEnum) {
            return $state->value;
        }

        if ($state === null || $state === '') {
            return $state;
        }

        return (int) $state;
    }

    private static function resolveEquipeTrabalhoStatus(mixed $state): ?LiberacaoInscricoesEquipeTrabalhoStatusEnum
    {
        if ($state instanceof LiberacaoInscricoesEquipeTrabalhoStatusEnum) {
            return $state;
        }

        if (blank($state)) {
            return null;
        }

        return LiberacaoInscricoesEquipeTrabalhoStatusEnum::tryFrom((int) $state);
    }

    private static function resolveCampistaStatus(mixed $state): ?LiberacaoInscricoesStatusEnum
    {
        if ($state instanceof LiberacaoInscricoesStatusEnum) {
            return $state;
        }

        if (blank($state)) {
            return null;
        }

        return LiberacaoInscricoesStatusEnum::tryFrom((int) $state);
    }
}
