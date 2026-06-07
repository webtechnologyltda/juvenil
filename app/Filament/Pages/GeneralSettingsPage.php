<?php

namespace App\Filament\Pages;

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Settings\GeneralSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;
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
                                    ->options(LiberacaoInscricoesStatusEnum::class)
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
                                    ->options(LiberacaoInscricoesEquipeTrabalhoStatusEnum::class)
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->prefixIcon(fn ($state) => self::resolveEquipeTrabalhoStatus($state)?->getIcon())
                                    ->prefixIconColor(fn ($state) => self::resolveEquipeTrabalhoStatus($state)?->getColor())
                                    ->columnSpanFull(),
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
                                    ->imagePreviewHeight('160')
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
                                    ->required()
                                    ->columnSpan([
                                        'default' => 'full',
                                    ]),

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

                        Section::make('Mensagem de Bloqueio')
                            ->description('Conteúdo exibido quando as inscrições dos campistas não estiverem liberadas.')
                            ->schema([
                                RichEditor::make('liberacao_inscricoes_bloco')
                                    ->label('Conteúdo bloco de inscrições dos campistas')
                                    ->hint('O conteúdo do bloco de inscrições aparece quando o status das inscrições estiver
                                        diferente de '.LiberacaoInscricoesStatusEnum::LIBERADO->getLabel())
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintColor('warning')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('settings')
                                    ->fileAttachmentsMaxSize(1024)
                                    ->required(fn (Get $get) => self::resolveCampistaStatus($get('liberacao_inscricoes_status')) !== LiberacaoInscricoesStatusEnum::LIBERADO),
                            ])
                            ->columnSpan([
                                'default' => 'full',
                            ])
                            ->columns(12)
                            ->icon('heroicon-o-megaphone'),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['liberacao_inscricoes_status'] = self::normalizeIntegerSettingValue(
            $data['liberacao_inscricoes_status'] ?? null,
        );
        $data['liberacao_inscricoes_equipe_trabalho_status'] = self::normalizeIntegerSettingValue(
            $data['liberacao_inscricoes_equipe_trabalho_status'] ?? null,
        );

        return $data;
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
