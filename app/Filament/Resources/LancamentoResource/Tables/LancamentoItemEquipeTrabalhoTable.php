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

                return app(RegistrationPaymentAllocator::class)->applyPaymentEligibilityQuery(
                    query: $query
                        ->select(['id', 'nome', 'avatar_url', 'data_form', 'status', 'tribo_id', 'descricao', 'tipo_equipe'])
                        ->with('tribo:id,cor,cor_hex'),
                    registrationType: EquipeTrabalho::class,
                    excludingLancamentoId: $arguments['excluding_lancamento_id'] ?? null,
                    currentRegistrationId: $arguments['current_registration_id'] ?? null,
                    categoryId: $arguments['categoria_lancamento_id'] ?? null,
                );
            })
            ->columns(EquipeTrabalhoTable::getColumns())
            ->filters(EquipeTrabalhoTable::getFilters())
            ->defaultSort('id', 'desc')
            ->paginationPageOptions([5, 10, 30, 50])
            ->extremePaginationLinks()
            ->striped();
    }
}
