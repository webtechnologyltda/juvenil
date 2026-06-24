<?php

namespace App\Filament\Pages;

use App\Actions\Reports\EnsureReportExportAction;
use App\Enums\ReportExportStatus;
use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\Models\User;
use App\Support\Reports\CampistaReportType;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Width;
use UnitEnum;

class ViewReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Pré-visualização do relatório';

    protected string $view = 'filament.pages.view-report';

    public string $reportType = 'registration_fichas';

    /** @var array<string, mixed> */
    public array $filters = [];

    public ?int $reportExportId = null;

    public static function getRoutePath(Panel $panel): string
    {
        return '/relatorios/preview';
    }

    /**
     * @param  array<int, int>|null  $status
     * @param  array<int, int>|null  $tribo_id
     */
    public function mount(
        ?string $type = null,
        ?array $status = null,
        ?array $tribo_id = null,
        ?string $presenca = null,
        ?string $search = null,
        mixed $show_sensitive_health = null,
        mixed $confirm_sensitive_health = null,
        mixed $show_payment_data = null,
    ): void {
        $query = request()->query();
        $queryParams = is_array($query) ? $query : [];

        $merged = array_merge($queryParams, array_filter([
            'type' => $type,
            'status' => $status,
            'tribo_id' => $tribo_id,
            'presenca' => $presenca,
            'search' => $search,
            'show_sensitive_health' => $show_sensitive_health,
            'confirm_sensitive_health' => $confirm_sensitive_health,
            'show_payment_data' => $show_payment_data,
        ], static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []));

        $reportType = CampistaReportType::tryFrom((string) ($merged['type'] ?? CampistaReportType::RegistrationFichas->value));

        abort_unless($reportType instanceof CampistaReportType, 404);

        $user = Filament::auth()->user();

        abort_unless($user instanceof User, 403);
        abort_unless($reportType->canBeAccessedBy($user), 403);

        $this->reportType = $reportType->value;
        $this->filters = $this->filtersFromQuery($merged);

        $this->ensureReportExport();
    }

    public function getTitle(): string
    {
        return $this->type()->title();
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        return 'A geração roda em segundo plano. A prévia aparece automaticamente quando o arquivo imprimível estiver pronto.';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::SevenExtraLarge;
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Voltar')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(fn (): string => route('filament.admin.pages.reports-page', $this->backQuery())),
            Action::make('download')
                ->label('Baixar arquivo')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn (): string => $this->fileUrl(inline: false))
                ->disabled(fn (): bool => ! $this->isReportReady())
                ->openUrlInNewTab(),
            Action::make('retry')
                ->label('Gerar novamente')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => $this->reportExport()?->isFailed() ?? false)
                ->action('retryReportExport'),
        ];
    }

    public function reportExport(): ?ReportExport
    {
        if ($this->reportExportId === null) {
            return null;
        }

        return ReportExport::query()->find($this->reportExportId);
    }

    public function refreshReportExport(): void {}

    public function retryReportExport(): void
    {
        $reportExport = $this->reportExport();

        if ($reportExport === null) {
            $this->ensureReportExport();

            return;
        }

        $reportExport->forceFill([
            'status' => ReportExportStatus::Pending,
            'error_message' => null,
            'progress_current' => 0,
            'progress_total' => 100,
            'progress_message' => 'Na fila para geração.',
            'started_at' => null,
            'finished_at' => null,
            'expires_at' => now()->addDays(2),
        ])->save();

        $pendingDispatch = GenerateReportExport::dispatch($reportExport->id)
            ->onQueue((string) config('reports.exports.queue', 'default'));

        if (filled($connection = config('reports.exports.queue_connection'))) {
            $pendingDispatch->onConnection((string) $connection);
        }
    }

    public function isReportReady(): bool
    {
        return $this->reportExport()?->isReady() ?? false;
    }

    public function fileUrl(bool $inline = true): string
    {
        if ($this->reportExportId === null) {
            return '#';
        }

        return route('admin.reports.exports.file', [
            'reportExport' => $this->reportExportId,
            'inline' => $inline ? '1' : '0',
        ]);
    }

    private function ensureReportExport(): void
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $reportExport = app(EnsureReportExportAction::class)->execute(
            user: $user,
            type: $this->type(),
            filters: $this->filters,
        );

        $this->reportExportId = $reportExport->id;
    }

    private function type(): CampistaReportType
    {
        return CampistaReportType::from($this->reportType);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    private function filtersFromQuery(array $query): array
    {
        return collect([
            'type' => $this->reportType,
            'status' => $this->integerList($query['status'] ?? []),
            'tribo_id' => $this->integerList($query['tribo_id'] ?? []),
            'presenca' => array_key_exists('presenca', $query) && $query['presenca'] !== ''
                ? (string) (int) $query['presenca']
                : null,
            'search' => filled($query['search'] ?? null) ? (string) $query['search'] : null,
            'show_sensitive_health' => $this->truthy($query['show_sensitive_health'] ?? false),
            'confirm_sensitive_health' => $this->truthy($query['confirm_sensitive_health'] ?? false),
            'show_payment_data' => $this->truthy($query['show_payment_data'] ?? false),
        ])
            ->filter(fn (mixed $value): bool => $value !== null && $value !== '' && $value !== [] && $value !== false)
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function backQuery(): array
    {
        return collect($this->filters)
            ->except('_template_version')
            ->all();
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
