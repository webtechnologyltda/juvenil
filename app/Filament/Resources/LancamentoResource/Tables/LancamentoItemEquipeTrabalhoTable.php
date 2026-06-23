<?php

namespace App\Filament\Resources\LancamentoResource\Tables;

use App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoTable;
use App\Models\EquipeTrabalho;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LancamentoItemEquipeTrabalhoTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(EquipeTrabalho::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();

                return $query->whereKey(array_keys(app(RegistrationPaymentAllocator::class)->registrationOptions(
                    EquipeTrabalho::class,
                    $arguments['excluding_lancamento_id'] ?? null,
                    $arguments['current_registration_id'] ?? null,
                )));
            })
            ->columns(EquipeTrabalhoTable::getColumns())
            ->filters(EquipeTrabalhoTable::getFilters())
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([5, 10, 30, 50])
            ->extremePaginationLinks()
            ->striped();
    }
}
