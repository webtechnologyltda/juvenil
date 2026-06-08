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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard as FilamentDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
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
                    ->multiple()
                    ->options(StatusInscricao::class)
                    ->placeholder('Válidas'),
                Select::make('tribo_id')
                    ->label('Tribo')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => Tribo::query()->orderBy('cor')->pluck('cor', 'id')->all()),
                Select::make('paroquia')
                    ->label('Paróquia')
                    ->options([
                        0 => 'São Domingos e Nossa Senhora do Carmo',
                        1 => 'Santa Luzia',
                        2 => 'Outra paróquia',
                    ]),
                TextInput::make('comunidade')
                    ->label('Comunidade'),
                Select::make('presenca')
                    ->label('Presença')
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
}
