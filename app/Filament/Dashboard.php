<?php

namespace App\Filament;

use App\Enums\StatusInscricao;
use App\Filament\Widgets\Operational\CommunityDistributionChart;
use App\Filament\Widgets\Operational\DemographicsChart;
use App\Filament\Widgets\Operational\OperationalFunnelChart;
use App\Filament\Widgets\Operational\OperationalPendingTasksTable;
use App\Filament\Widgets\Operational\OperationalPipelineStats;
use App\Filament\Widgets\Operational\RegistrationTrendChart;
use App\Filament\Widgets\Operational\SensitiveHealthSummaryStats;
use App\Filament\Widgets\Operational\SensitiveHealthTable;
use App\Filament\Widgets\Operational\SexDistributionChart;
use App\Filament\Widgets\Operational\ShirtSizeChart;
use App\Filament\Widgets\Operational\TribeDistributionChart;
use App\Models\Tribo;
use App\Support\Campistas\ParishCommunityLabels;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard as FilamentDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class Dashboard extends FilamentDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Início';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('status')
                    ->label('Status')
                    ->native(false)
                    ->multiple()
                    ->options(StatusInscricao::class)
                    ->placeholder('Válidas'),
                Select::make('tribo_id')
                    ->label('Tribo')
                    ->native(false)
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Tribo::query()->orderBy('cor')->pluck('cor', 'id')->all()),
                Select::make('paroquia')
                    ->label('Paróquia')
                    ->native(false)
                    ->options(ParishCommunityLabels::parishOptions(short: true))
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('comunidade', []);
                        $set('comunidade_texto', null);
                    }),
                Select::make('comunidade')
                    ->label('Comunidade')
                    ->native(false)
                    ->multiple()
                    ->searchable()
                    ->options(fn (Get $get): array => ParishCommunityLabels::communityOptions($get('paroquia'), short: true))
                    ->visible(fn (Get $get): bool => filled($get('paroquia')) && ! self::selectedParishIs($get, 2)),
                TextInput::make('comunidade_texto')
                    ->label('Comunidade')
                    ->visible(fn (Get $get): bool => self::selectedParishIs($get, 2))
                    ->placeholder('Digite parte do nome')
                    ->live(debounce: 500),
                Select::make('presenca')
                    ->label('Presença')
                    ->native(false)
                    ->options([
                        0 => 'Aguardando check-in',
                        1 => 'Presente',
                    ]),
            ]);
    }

    public function getWidgets(): array
    {
        return static::getOperationalWidgets();
    }

    public static function getOperationalWidgets(): array
    {
        return [
            OperationalPipelineStats::class,
            SensitiveHealthSummaryStats::class,
            OperationalFunnelChart::class,
            RegistrationTrendChart::class,
            TribeDistributionChart::class,
            ShirtSizeChart::class,
            CommunityDistributionChart::class,
            DemographicsChart::class,
            SexDistributionChart::class,
            OperationalPendingTasksTable::class,
            SensitiveHealthTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'xl' => 2,
        ];
    }

    private static function selectedParishIs(Get $get, int $parish): bool
    {
        $selectedParish = $get('paroquia');

        return $selectedParish !== null
            && $selectedParish !== ''
            && (int) $selectedParish === $parish;
    }
}
