<?php

namespace App\Filament;

use Filament\Pages\Dashboard as FilamentDashboard;

class Dashboard extends FilamentDashboard
{
    protected static ?string $title = 'Início';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';
}
