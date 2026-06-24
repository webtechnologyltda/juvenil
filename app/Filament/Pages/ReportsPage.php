<?php

namespace App\Filament\Pages;

use App\Enums\StatusInscricao;
use App\Support\Reports\CampistaReportData;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class ReportsPage extends Page
{
    use HasPageShield;

    protected static ?string $slug = 'reports-page';

    protected static ?string $title = 'Relatórios dinâmicos';

    protected static ?string $navigationLabel = 'Relatórios';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-printer';

    protected static string|\UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.reports-page';

    public array $filters = [
        'type' => null,
        'status' => [
            StatusInscricao::Pendente->value,
            StatusInscricao::Pago->value,
        ],
        'tribo_id' => [],
        'presenca' => null,
        'search' => null,
        'show_sensitive_health' => false,
        'confirm_sensitive_health' => false,
        'show_payment_data' => false,
    ];

    public static function getRoutePath(Panel $panel): string
    {
        return '/relatorios';
    }

    public function mount(): void
    {
        $this->filters = $this->filtersFromQuery(request()->query());
        $this->filters['type'] ??= $this->defaultReportType();

        $this->form->fill($this->filters);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('filters')
            ->columns(1)
            ->components([
                Section::make('Montar relatório')
                    ->description('Escolha o tipo, aplique os filtros e gere a prévia no mesmo navegador.')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->headerActions([
                        Action::make('reportHelp')
                            ->label('Dúvidas')
                            ->icon('heroicon-o-question-mark-circle')
                            ->color('gray')
                            ->slideOver()
                            ->modalWidth(Width::Large)
                            ->modalHeading('Dúvidas da central de relatórios')
                            ->modalDescription('Use esta referência para escolher o relatório certo antes de abrir a prévia.')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Fechar')
                            ->modalContent(fn () => view('filament.pages.partials.reports-help', [
                                'types' => $this->reportTypes(),
                            ])),
                    ])
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 12,
                        ])
                            ->schema([
                                Select::make('type')
                                    ->label('Tipo de relatório')
                                    ->options(fn (): array => collect($this->reportTypes())->pluck('label', 'value')->all())
                                    ->native(false)
                                    ->searchable()
                                    ->live()
                                    ->required()
                                    ->selectablePlaceholder(false)
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 2,
                                        'xl' => 4,
                                    ]),

                                TextInput::make('search')
                                    ->label('Busca')
                                    ->placeholder('Nome, responsável, bairro ou cidade')
                                    ->prefixIcon('heroicon-o-magnifying-glass')
                                    ->live(debounce: 500)
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 2,
                                        'xl' => 8,
                                    ]),

                                Select::make('status')
                                    ->label('Status')
                                    ->multiple()
                                    ->options(fn (): array => $this->statusOptions())
                                    ->native(false)
                                    ->live()
                                    ->placeholder('Pendente e Pago')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 4,
                                    ]),

                                Select::make('tribo_id')
                                    ->label('Tribo')
                                    ->multiple()
                                    ->options(fn (): array => $this->tribeOptions())
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->placeholder('Todas')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 4,
                                    ]),

                                Select::make('presenca')
                                    ->label('Presença')
                                    ->options([
                                        1 => 'Confirmada',
                                        0 => 'Pendente',
                                    ])
                                    ->native(false)
                                    ->live()
                                    ->placeholder('Todas')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 4,
                                    ]),

                                Toggle::make('show_sensitive_health')
                                    ->label('Exibir dados médicos')
                                    ->helperText('Por padrão, dados médicos permanecem ocultos nos relatórios.')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, bool $state): void {
                                        if (! $state) {
                                            $set('confirm_sensitive_health', false);
                                        }
                                    })
                                    ->visible(fn (): bool => $this->canUseSensitiveHealthFilter())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 6,
                                    ]),

                                Toggle::make('show_payment_data')
                                    ->label('Exibir dados de pagamento')
                                    ->helperText('Por padrão, dados de pagamento permanecem ocultos nos relatórios.')
                                    ->live()
                                    ->visible(fn (): bool => $this->canUsePaymentDataFilter())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 6,
                                    ]),

                                Html::make(new HtmlString(
                                    '<div class="rounded-lg border border-primary-500/45 bg-primary-500/10 p-4 text-sm text-primary-100">
                                        <strong class="block text-primary-300">Dados médicos sensíveis</strong>
                                        <span>Ao exibir estes dados, trate as informações com cuidado. Elas não devem ser compartilhadas fora das pessoas responsáveis pelo cuidado e pela operação do acampamento.</span>
                                    </div>'
                                ))
                                    ->visible(fn (Get $get): bool => $this->canUseSensitiveHealthFilter() && (bool) $get('show_sensitive_health'))
                                    ->columnSpanFull(),

                                Checkbox::make('confirm_sensitive_health')
                                    ->label('Confirmo que desejo exibir dados médicos sensíveis neste relatório.')
                                    ->helperText('A impressão só exibirá os dados médicos após esta confirmação.')
                                    ->accepted()
                                    ->live()
                                    ->visible(fn (Get $get): bool => $this->canUseSensitiveHealthFilter() && (bool) $get('show_sensitive_health'))
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->footerActions([
                        Action::make('openPreview')
                            ->label('Abrir prévia')
                            ->icon('heroicon-o-document-magnifying-glass')
                            ->color('primary')
                            ->url(fn (): string => ViewReport::getUrl($this->previewQuery()))
                            ->extraAttributes(['data-report-preview-link' => 'true'])
                            ->disabled(fn (): bool => blank($this->filters['type'] ?? null) || $this->missingSensitiveHealthConfirmation()),
                    ])
                    ->footerActionsAlignment(Alignment::End),
            ]);
    }

    public function reportTypes(): array
    {
        return app(CampistaReportData::class)->availableTypes(auth()->user());
    }

    public function statusOptions(): array
    {
        return app(CampistaReportData::class)->statusOptions();
    }

    public function tribeOptions(): array
    {
        return app(CampistaReportData::class)->tribeOptions();
    }

    public function previewQuery(): array
    {
        $query = collect($this->filters)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [] && $value !== false)
            ->all();

        return [
            ...$query,
        ];
    }

    private function canUseSensitiveHealthFilter(): bool
    {
        return auth()->user()?->can('view_sensitive_health_campista') ?? false;
    }

    private function canUsePaymentDataFilter(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->can('view_any_lancamento')
            && $user->can('view_lancamento');
    }

    private function missingSensitiveHealthConfirmation(): bool
    {
        return $this->canUseSensitiveHealthFilter()
            && (bool) ($this->filters['show_sensitive_health'] ?? false)
            && ! (bool) ($this->filters['confirm_sensitive_health'] ?? false);
    }

    private function defaultReportType(): ?string
    {
        return data_get($this->reportTypes(), '0.value');
    }

    private function filtersFromQuery(array $query): array
    {
        $filters = $this->filters;

        if (array_key_exists('type', $query)) {
            $filters['type'] = filled($query['type']) ? (string) $query['type'] : null;
        }

        if (array_key_exists('status', $query)) {
            $filters['status'] = $this->integerList($query['status']);
        }

        if (array_key_exists('tribo_id', $query)) {
            $filters['tribo_id'] = $this->integerList($query['tribo_id']);
        }

        if (array_key_exists('presenca', $query)) {
            $filters['presenca'] = $query['presenca'] === '' || $query['presenca'] === null
                ? null
                : (string) (int) $query['presenca'];
        }

        if (array_key_exists('search', $query)) {
            $filters['search'] = filled($query['search']) ? (string) $query['search'] : null;
        }

        foreach (['show_sensitive_health', 'confirm_sensitive_health', 'show_payment_data'] as $key) {
            if (array_key_exists($key, $query)) {
                $filters[$key] = $this->truthy($query[$key]);
            }
        }

        return $filters;
    }

    private function integerList(mixed $values): array
    {
        $values = is_array($values) ? $values : [$values];

        return collect($values)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '')
            ->map(fn (mixed $value): int => (int) $value)
            ->values()
            ->all();
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
