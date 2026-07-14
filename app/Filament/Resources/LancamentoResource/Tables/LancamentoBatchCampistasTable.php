<?php

namespace App\Filament\Resources\LancamentoResource\Tables;

use App\Filament\Resources\CampistaResource\CampistaTable;
use App\Models\Campista;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LancamentoBatchCampistasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(RegistrationPaymentAllocator::class)->applyPaymentEligibilityQuery(
                query: Campista::query(),
                registrationType: Campista::class,
            ))
            ->columns(CampistaTable::getListTableColumns())
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([5, 10, 30, 50])
            ->extremePaginationLinks()
            ->striped();
    }
}
