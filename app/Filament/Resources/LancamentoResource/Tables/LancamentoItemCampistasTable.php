<?php

namespace App\Filament\Resources\LancamentoResource\Tables;

use App\Filament\Resources\CampistaResource\CampistaTable;
use App\Models\Campista;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LancamentoItemCampistasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(Campista::query())
            ->modifyQueryUsing(function (Builder $query) use ($table): Builder {
                $arguments = $table->getArguments();

                return $query->whereKey(array_keys(app(RegistrationPaymentAllocator::class)->registrationOptions(
                    Campista::class,
                    $arguments['excluding_lancamento_id'] ?? null,
                    $arguments['current_registration_id'] ?? null,
                    $arguments['categoria_lancamento_id'] ?? null,
                )));
            })
            ->columns(CampistaTable::getListTableColumns())
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([5, 10, 30, 50])
            ->extremePaginationLinks()
            ->striped();
    }
}
