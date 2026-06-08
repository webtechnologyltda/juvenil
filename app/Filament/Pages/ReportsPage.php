<?php

namespace App\Filament\Pages;

use App\Enums\StatusInscricao;
use App\Support\Reports\CampistaReportData;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;

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
    ];

    public static function getRoutePath(Panel $panel): string
    {
        return '/relatorios';
    }

    public function mount(): void
    {
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
                    ->description('Escolha o tipo e refine a prévia antes de imprimir.')
                    ->icon('heroicon-o-adjustments-horizontal')
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
                                        'md' => 1,
                                        'xl' => 3,
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
                                        'xl' => 3,
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
                                        'xl' => 3,
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
                                        'xl' => 3,
                                    ]),

                                TextInput::make('search')
                                    ->label('Busca')
                                    ->placeholder('Nome, responsável, bairro ou cidade')
                                    ->prefixIcon('heroicon-o-magnifying-glass')
                                    ->live(debounce: 500)
                                    ->columnSpan([
                                        'default' => 'full',
                                        'xl' => 9,
                                    ]),
                            ]),
                    ])
                    ->footerActions([
                        Action::make('openPreview')
                            ->label('Abrir prévia')
                            ->icon('heroicon-o-printer')
                            ->color('primary')
                            ->url(fn (): string => route('admin.reports.print', $this->previewQuery()))
                            ->openUrlInNewTab()
                            ->disabled(fn (): bool => blank($this->filters['type'] ?? null)),
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
        return collect($this->filters)
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [])
            ->all();
    }

    private function defaultReportType(): ?string
    {
        return data_get($this->reportTypes(), '0.value');
    }
}
