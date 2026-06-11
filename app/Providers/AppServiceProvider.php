<?php

namespace App\Providers;

use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Observers\CampistaObserver;
use App\Observers\EquipeTrabalhoObserver;
use App\Support\Livewire\FilamentNotificationsWireableSynth;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Campista::observe(CampistaObserver::class);
        EquipeTrabalho::observe(EquipeTrabalhoObserver::class);

        FilamentColor::register([
            'primary' => Color::generatePalette('#f46b12'),
        ]);

        Table::configureUsing(function (Table $table): void {
            $table->deferColumnManager(false);
        });

        Livewire::propertySynthesizer(FilamentNotificationsWireableSynth::class);
    }
}
