<?php

namespace App\Filament\Resources\LancamentoResource\Tables;

use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoTable;
use App\Models\EquipeTrabalho;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LancamentoBatchEquipeTrabalhoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => app(RegistrationPaymentAllocator::class)->applyPaymentEligibilityQuery(
                query: EquipeTrabalho::query(),
                registrationType: EquipeTrabalho::class,
            ))
            ->columns(EquipeTrabalhoTable::getColumns())
            ->filters(EquipeTrabalhoTable::getFilters())
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([5, 10, 30, 50])
            ->extremePaginationLinks()
            ->striped();
    }
}
