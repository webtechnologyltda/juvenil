<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Enums\StatusInscricao;
use App\Filament\Resources\CampistaResource;
use App\Models\Campista;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;
use Illuminate\Database\Eloquent\Builder;

class ListCampistas extends ListRecords
{
    protected static string $resource = CampistaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->icon('heroicon-s-plus')
            ->label('Criar inscrição'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Todas' => Tab::make()
            ->icon('eos-all-inclusive')
            ->badge(Campista::count()),
            StatusInscricao::Pendente->name => Tab::make()
                ->icon(StatusInscricao::Pendente->getIcon())
                ->badge(Campista::where('status', StatusInscricao::Pendente)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusInscricao::Pendente)),
            StatusInscricao::Pago->name => Tab::make()
                ->icon(StatusInscricao::Pago->getIcon())
                ->badge(Campista::where('status', StatusInscricao::Pago)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusInscricao::Pago)),
            StatusInscricao::Cancelado->name => Tab::make()
                ->icon(StatusInscricao::Cancelado->getIcon())
                ->badge(Campista::where('status', StatusInscricao::Cancelado)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusInscricao::Cancelado)),
        ];
    }
}
