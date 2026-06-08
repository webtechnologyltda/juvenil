<?php

namespace App\Filament\Resources\LancamentoResource\Tables;

use App\Filament\Resources\CampistaResource\CampistaTable;
use App\Models\Campista;
use App\Support\Financeiro\LancamentoBatchCreator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LancamentoBatchCampistasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Campista::query()
                ->whereKey(array_keys(app(LancamentoBatchCreator::class)->registrationOptions(Campista::class))))
            ->columns(CampistaTable::getListTableColumns())
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([5, 10, 30, 50])
            ->extremePaginationLinks()
            ->striped();
    }
}
