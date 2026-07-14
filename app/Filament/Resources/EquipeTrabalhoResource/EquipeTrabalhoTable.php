<?php

namespace App\Filament\Resources\EquipeTrabalhoResource;

use App\Enums\StatusInscricaoEquipeTrabalho;
use App\Enums\TipoEquipeTrabalho;
use App\Models\EquipeTrabalho;
use App\Support\Financeiro\FinancialFilterOptions;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

abstract class EquipeTrabalhoTable
{
    public static function getColumns(): array
    {
        return [
            TextColumn::make('id')
                ->label('Cód.')
                ->sortable()
                ->searchable()
                ->visibleFrom('md'),

            ImageColumn::make('avatar_url')
                ->state(fn ($record): ?string => filter_var($record->avatar_url, FILTER_VALIDATE_URL) ? null : $record->avatar_url)
                ->disk('public')
                ->square()
                ->alignCenter()
                ->size(60)
                ->grow(false)
                ->defaultImageUrl(asset('img/logo.png'))
                ->extraImgAttributes([
                    'class' => 'rounded-xl juvenil-admin-table-avatar',
                    'onerror' => "this.onerror=null;this.src='".asset('img/logo.png')."';",
                ])
                ->label('Foto'),

            TextColumn::make('nome')
                ->lineClamp(1)
                ->sortable()
                ->searchable(),

            TextColumn::make('descricao')
                ->label('Equipe')
                ->badge()
                ->placeholder('Sem equipe')
                ->sortable()
                ->searchable(),

            TextColumn::make('tipo_equipe')
                ->label('Tipo')
                ->badge()
                ->sortable(),

            TextColumn::make('data_form.data_nacimento')
                ->label('Idade')
                ->numeric()
                ->sortable()
                ->alignCenter()
                ->formatStateUsing(fn (mixed $state): ?int => self::ageFromBirthDate($state))
                ->placeholder('-')
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),

            SelectColumn::make('status')
                ->alignCenter()
                ->options(StatusInscricaoEquipeTrabalho::class)
                ->disabled(fn (EquipeTrabalho $record): bool => ! auth()->user()?->can('update', $record))
                ->label('Status')
                ->visibleFrom('md'),

            TextColumn::make('data_form.sexo')
                ->alignCenter()
                ->badge()
                ->toggleable(isToggledHiddenByDefault: true)
                ->icon(fn ($state) => $state === 'M' ? 'eos-male' : 'eos-female')
                ->formatStateUsing(fn ($state) => $state === 'M' ? 'Masculino' : 'Feminino')
                ->color(fn ($state) => $state === 'M' ? Color::Blue : Color::Pink)
                ->sortable()
                ->label('Sexo')
                ->visibleFrom('md'),

            TextColumn::make('data_form.tamanho_camiseta')
                ->label('Tamanho da Camiseta')
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),

            TextColumn::make('data_form.reponsavel_nome')
                ->label('Nome do Responsável')
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),

            TextColumn::make('data_form.reponsavel_telefone')
                ->label('Telefone Responsável')
                ->alignCenter()
                ->toggleable(isToggledHiddenByDefault: true)
                ->visibleFrom('md'),
        ];
    }

    public static function getFilters(): array
    {
        return [
            SelectFilter::make('descricao')
                ->label('Equipe')
                ->options(fn (): array => EquipeTrabalho::query()
                    ->whereNotNull('descricao')
                    ->where('descricao', '!=', '')
                    ->distinct()
                    ->orderBy('descricao')
                    ->pluck('descricao', 'descricao')
                    ->all())
                ->searchable()
                ->preload(),

            SelectFilter::make('tipo_equipe')
                ->label('Tipo da equipe')
                ->options(TipoEquipeTrabalho::class),

            SelectFilter::make('payment_status')
                ->label('Status do pagamento')
                ->options(fn (): array => FinancialFilterOptions::paymentStatuses())
                ->multiple()
                ->query(function (Builder $query, array $data): Builder {
                    $statuses = FinancialFilterOptions::selectedIntegerValues($data);

                    return $statuses === []
                        ? $query
                        : $query->whereHas(
                            'lancamentoItems.lancamento',
                            fn (Builder $query): Builder => $query->whereIn('status', $statuses),
                        );
                })
                ->searchable()
                ->preload()
                ->native(false)
                ->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml())
                ->indicateUsing(fn (array $state): array => FinancialFilterOptions::paymentStatusIndicators($state)),

            SelectFilter::make('categoria_lancamento_id')
                ->label('Categoria')
                ->options(fn (): array => FinancialFilterOptions::categories())
                ->multiple()
                ->query(function (Builder $query, array $data): Builder {
                    $categoryIds = FinancialFilterOptions::selectedIntegerValues($data);

                    return $categoryIds === []
                        ? $query
                        : $query->whereHas(
                            'lancamentoItems',
                            fn (Builder $query): Builder => $query->whereIn('categoria_lancamento_id', $categoryIds),
                        );
                })
                ->searchable()
                ->preload()
                ->native(false)
                ->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml())
                ->indicateUsing(fn (array $state): array => FinancialFilterOptions::categoryIndicators($state)),
        ];
    }

    private static function ageFromBirthDate(mixed $state): ?int
    {
        if (! is_string($state) || trim($state) === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $state)->age;
        } catch (Throwable) {
            return null;
        }
    }
}
