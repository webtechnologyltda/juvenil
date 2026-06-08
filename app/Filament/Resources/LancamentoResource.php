<?php

namespace App\Filament\Resources;

use App\Enums\TipoLacamento;
use App\Filament\Exports\LancamentoExporter;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\Pages;
use App\Filament\Resources\LancamentoResource\Widgets\StatsFinanceiro;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
use App\Support\IconBadge;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class LancamentoResource extends Resource
{
    protected static ?string $model = Lancamento::class;

    protected static string|\BackedEnum|null $navigationIcon = 'clarity-file-group-line';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $label = 'Lançamento';

    protected static ?string $pluralLabel = 'Lançamentos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components(LancamentoForm::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['items.categoria', 'items.registration']))
            ->columns([
                TextColumn::make('id')
                    ->label('Código')
                    ->searchable(),

                TextColumn::make('nome')
                    ->label('Nome do Lançamento')
                    ->searchable(),

                TextColumn::make('valor')
                    ->prefix(fn (Lancamento $record) => ($record->tipo == TipoLacamento::Despesa ? '-' : '').'R$ ')
                    ->label('Valor')
                    ->formatStateUsing(fn (int $state, Lancamento $record) => number_format($state / ($record->tipo == TipoLacamento::Despesa ? -100 : 100), 2, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->label('Total')
                            ->money('BRL', locale: 'pt_BR', divideBy: 100)
                    )
                    ->sortable(),

                TextColumn::make('tipo')
                    ->badge()
                    ->alignCenter()
                    ->label('Lançamento'),

                TextColumn::make('categories_summary')
                    ->label('Categorias')
                    ->html()
                    ->formatStateUsing(fn (mixed $state, Lancamento $record): HtmlString => self::categoryBadges($record))
                    ->tooltip(fn (Lancamento $record): string => $record->categories_summary)
                    ->placeholder('Sem categoria')
                    ->alignCenter(),

                TextColumn::make('registration_payments_summary')
                    ->label('Inscrições')
                    ->formatStateUsing(fn (?string $state): string => nl2br(e($state ?? 'Sem inscrições vinculadas')))
                    ->html()
                    ->placeholder('Sem inscrições vinculadas')
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->alignCenter()
                    ->label('Status'),

                TextColumn::make('batch_code')
                    ->label('Lote')
                    ->badge()
                    ->placeholder('Sem lote')
                    ->searchable(),

                TextColumn::make('data')
                    ->alignCenter()
                    ->label('Data lançamento')
                    ->dateTime('d/m/Y')
                    ->sortable(),

            ])
            ->groups([
                Group::make('status')->collapsible(),
                Group::make('tipo')->collapsible(),
                Group::make('batch_code')
                    ->label('Lote')
                    ->collapsible(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('categoria_lancamento_id')
                    ->label('Categoria')
                    ->options(fn (): array => CategoriaLancamento::query()
                        ->orderBy('nome')
                        ->pluck('nome', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => blank($data['value'] ?? null)
                        ? $query
                        : $query->whereHas('items', fn (Builder $query): Builder => $query->where('categoria_lancamento_id', $data['value'])))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('batch_code')
                    ->label('Lote')
                    ->options(fn (): array => Lancamento::query()
                        ->whereNotNull('batch_code')
                        ->distinct()
                        ->orderByDesc('batch_code')
                        ->pluck('batch_code', 'batch_code')
                        ->all())
                    ->searchable(),
            ])
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exporter(LancamentoExporter::class)
                    ->fileName(fn (Export $export): string => 'lancamento-financeiro-'.Carbon::now()->format('YmdHis').'-'.$export->getKey())
                    ->label('Exportar'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLancamentos::route('/'),
            'create' => Pages\CreateLancamento::route('/create'),
            'batch' => Pages\BatchLancamentos::route('/batch'),
            'edit' => Pages\EditLancamento::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            StatsFinanceiro::class,
        ];
    }

    private static function categoryBadges(Lancamento $record): HtmlString
    {
        $items = $record->relationLoaded('items')
            ? $record->items
            : $record->items()->with('categoria')->get();

        $categories = $items
            ->map(fn (LancamentoItem $item) => $item->categoria)
            ->filter()
            ->unique('id')
            ->values();

        if ($categories->isEmpty()) {
            return new HtmlString('Sem categoria');
        }

        $visible = $categories
            ->take(2)
            ->map(fn (CategoriaLancamento $category): string => (string) IconBadge::tileIcon(
                $category,
                $category->nome,
                fallbackIcon: 'heroicon-o-tag',
            ))
            ->implode('');

        $extra = $categories->count() - 2;
        $extraBadge = $extra > 0
            ? '<span title="'.e($categories->skip(2)->pluck('nome')->implode(', ')).'" style="display:inline-flex;align-items:center;justify-content:center;width:2rem;height:2rem;border-radius:999px;background:rgba(148,163,184,.26);color:#f4fbfd;font-size:.75rem;font-weight:900;">+'.e((string) $extra).'</span>'
            : '';

        return new HtmlString('<span style="display:inline-flex;align-items:center;gap:.25rem;">'.$visible.$extraBadge.'</span>');
    }
}
