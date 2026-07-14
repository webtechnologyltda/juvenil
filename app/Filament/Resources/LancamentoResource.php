<?php

namespace App\Filament\Resources;

use App\Enums\TipoLacamento;
use App\Filament\Exports\LancamentoExporter;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\Pages;
use App\Filament\Resources\LancamentoResource\Widgets\StatsFinanceiro;
use App\Models\Campista;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
use App\Support\EnumOptionBadge;
use App\Support\Financeiro\FinancialFilterOptions;
use App\Support\IconBadge;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
            ->recordUrl(fn (Lancamento $record): string => static::getUrl('view', ['record' => $record]))
            ->extraAttributes(['class' => 'juvenil-lancamento-table'], merge: true)
            ->columns([
                TextColumn::make('id')
                    ->label('Cód.')
                    ->searchable()
                    ->width('4.5rem')
                    ->grow(false),

                TextColumn::make('nome')
                    ->label('Nome do Lançamento')
                    ->searchable()
                    ->lineClamp(1)
                    ->tooltip(fn (Lancamento $record): string => $record->nome)
                    ->width('18rem'),

                TextColumn::make('valor')
                    ->prefix(fn (Lancamento $record) => ($record->tipo == TipoLacamento::Despesa ? '-' : '').'R$ ')
                    ->label('Valor')
                    ->alignEnd()
                    ->formatStateUsing(fn (int $state, Lancamento $record) => number_format($state / ($record->tipo == TipoLacamento::Despesa ? -100 : 100), 2, ',', '.'))
                    ->summarize(
                        Tables\Columns\Summarizers\Sum::make()
                            ->numeric()
                            ->label('Total')
                            ->money('BRL', locale: 'pt_BR', divideBy: 100)
                    )
                    ->sortable()
                    ->width('7rem')
                    ->grow(false),

                TextColumn::make('tipo')
                    ->badge()
                    ->alignCenter()
                    ->label('Tipo')
                    ->width('7rem')
                    ->grow(false),

                TextColumn::make('categories_summary')
                    ->label('Categorias')
                    ->html()
                    ->formatStateUsing(fn (mixed $state, Lancamento $record): HtmlString => self::categoryBadges($record))
                    ->tooltip(fn (Lancamento $record): string => $record->categories_summary)
                    ->placeholder('Sem categoria')
                    ->alignCenter()
                    ->width('5.25rem')
                    ->grow(false),

                TextColumn::make('registration_payments_summary')
                    ->label('Itens lançados')
                    ->formatStateUsing(fn (mixed $state, Lancamento $record): HtmlString => self::registrationPaymentBadges($record))
                    ->html()
                    ->placeholder('Sem vínculos')
                    ->width('9.5rem')
                    ->grow(false),

                TextColumn::make('status')
                    ->badge()
                    ->alignCenter()
                    ->label('Status')
                    ->width('7rem')
                    ->grow(false),

                TextColumn::make('batch_code')
                    ->label('Lote')
                    ->badge()
                    ->placeholder('Sem lote')
                    ->searchable()
                    ->width('7.5rem')
                    ->grow(false),

                TextColumn::make('data')
                    ->alignCenter()
                    ->label('Data')
                    ->headerTooltip('Data do lançamento')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->width('7.25rem')
                    ->grow(false),

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
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(fn (): array => EnumOptionBadge::options(TipoLacamento::class))
                    ->native(false)
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml())
                    ->indicateUsing(fn (array $state): array => blank($state['value'] ?? null)
                        ? []
                        : ['Tipo: '.(TipoLacamento::tryFrom((int) $state['value'])?->getLabel() ?? $state['value'])]),
                SelectFilter::make('status')
                    ->label('Status do pagamento')
                    ->options(fn (): array => FinancialFilterOptions::paymentStatuses())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml())
                    ->indicateUsing(fn (array $state): array => FinancialFilterOptions::paymentStatusIndicators($state)),
                Filter::make('registration_link')
                    ->label('Cadastro vinculado')
                    ->form([
                        Select::make('linked')
                            ->label('Vínculo com cadastro')
                            ->options([
                                'linked' => 'Com cadastro vinculado',
                                'unlinked' => 'Sem cadastro vinculado',
                            ])
                            ->placeholder('Todos')
                            ->native(false)
                            ->live(),
                        Select::make('registration_type')
                            ->label('Tipo de cadastro')
                            ->options([
                                Campista::class => 'Campista',
                                EquipeTrabalho::class => 'Equipe de trabalho',
                            ])
                            ->placeholder('Todos')
                            ->native(false)
                            ->visible(fn (Get $get): bool => $get('linked') === 'linked'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $linked = $data['linked'] ?? null;
                        $registrationType = $data['registration_type'] ?? null;

                        if ($linked === 'linked') {
                            return $query->whereHas(
                                'items',
                                fn (Builder $query): Builder => self::registrationItemQuery($query, $registrationType),
                            );
                        }

                        if ($linked === 'unlinked') {
                            return $query->whereDoesntHave(
                                'items',
                                fn (Builder $query): Builder => self::registrationItemQuery($query),
                            );
                        }

                        if (filled($registrationType)) {
                            return $query->whereHas(
                                'items',
                                fn (Builder $query): Builder => self::registrationItemQuery($query, $registrationType),
                            );
                        }

                        return $query;
                    }),
                Filter::make('name_search')
                    ->label('Nome')
                    ->form([
                        TextInput::make('search')
                            ->label('Nome')
                            ->placeholder('Lançamento, campista ou equipe'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $search = trim((string) ($data['search'] ?? ''));

                        if ($search === '') {
                            return $query;
                        }

                        $like = self::likeSearch($search);

                        return $query->where(function (Builder $query) use ($like): Builder {
                            return $query
                                ->where('nome', 'like', $like)
                                ->orWhereHas(
                                    'items',
                                    fn (Builder $query): Builder => $query->whereHasMorph(
                                        'registration',
                                        [Campista::class, EquipeTrabalho::class],
                                        fn (Builder $query): Builder => $query->where('nome', 'like', $like),
                                    ),
                                );
                        });
                    }),
                Filter::make('created_at')
                    ->label('Período de criação')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Criado desde'),
                        DatePicker::make('created_until')
                            ->label('Criado até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                SelectFilter::make('categoria_lancamento_id')
                    ->label('Categoria')
                    ->options(fn (): array => self::categoryFilterOptions())
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        $categoryIds = collect($data['values'] ?? [])
                            ->filter(fn (mixed $value): bool => filled($value))
                            ->map(fn (mixed $value): int => (int) $value)
                            ->values()
                            ->all();

                        return $categoryIds === []
                            ? $query
                            : $query->whereHas('items', fn (Builder $query): Builder => $query->whereIn('categoria_lancamento_id', $categoryIds));
                    })
                    ->searchable()
                    ->native(false)
                    ->modifyFormFieldUsing(fn (Select $field): Select => $field->allowHtml())
                    ->indicateUsing(fn (array $state): array => FinancialFilterOptions::categoryIndicators($state))
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
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->toolbarActions([
                ExportBulkAction::make()
                    ->exporter(LancamentoExporter::class)
                    ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['items.categoria', 'items.registration']))
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
            'view' => Pages\ViewLancamento::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            StatsFinanceiro::class,
        ];
    }

    private static function registrationItemQuery(Builder $query, ?string $registrationType = null): Builder
    {
        $query
            ->whereNotNull('registration_type')
            ->whereNotNull('registration_id');

        if (filled($registrationType)) {
            $query->where('registration_type', $registrationType);
        }

        return $query;
    }

    private static function likeSearch(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }

    /**
     * @return array<int, string>
     */
    private static function categoryFilterOptions(): array
    {
        return FinancialFilterOptions::categories();
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
            return new HtmlString('<span class="juvenil-lancamento-table__empty" title="Sem categoria">Sem categ.</span>');
        }

        $visible = $categories
            ->take(2)
            ->map(fn (CategoriaLancamento $category): string => '<span class="juvenil-lancamento-table__category-stack-item">'
                .(string) IconBadge::tileIcon(
                    $category,
                    $category->nome,
                    fallbackIcon: 'heroicon-o-tag',
                )
                .'</span>')
            ->implode('');

        $extra = $categories->count() - 2;
        $extraBadge = $extra > 0
            ? '<span class="juvenil-lancamento-table__category-stack-extra" title="'.e($categories->skip(2)->pluck('nome')->implode(', ')).'">+'.e((string) $extra).'</span>'
            : '';

        return new HtmlString('<span class="juvenil-lancamento-table__category-stack">'.$visible.$extraBadge.'</span>');
    }

    private static function registrationPaymentBadges(Lancamento $record): HtmlString
    {
        $items = $record->relationLoaded('items')
            ? $record->items
            : $record->items()->with(['categoria', 'registration'])->get();

        $registrationItems = $items
            ->filter(fn (LancamentoItem $item): bool => filled($item->registration_type) && filled($item->registration_id))
            ->values();

        if ($registrationItems->isEmpty()) {
            return new HtmlString('<span class="juvenil-lancamento-table__empty">Sem vínculos</span>');
        }

        $itemCount = $registrationItems->count();
        $visibleItems = '<span class="juvenil-lancamento-table__registration-count">'
            .e((string) $itemCount).' '.e($itemCount === 1 ? 'item lançado' : 'itens lançados')
            .'</span>';

        $popoverItems = $registrationItems
            ->map(function (LancamentoItem $item): string {
                $registration = $item->registration;
                $type = $registration instanceof Campista ? 'Campista' : 'Equipe';
                $name = (string) ($registration?->getAttribute('nome') ?? 'Inscrição removida');
                $amount = 'R$ '.number_format($item->valor / 100, 2, ',', '.');
                $category = (string) ($item->categoria?->nome ?? 'Sem categoria');
                $itemName = (string) ($item->nome ?: $name);
                $categoryIcon = (string) IconBadge::tileIcon(
                    $item->categoria,
                    $category,
                    fallbackIcon: 'heroicon-o-tag',
                );

                return '<div class="juvenil-lancamento-table__popover-item">'
                    .'<span class="juvenil-lancamento-table__popover-category" aria-label="Categoria: '.e($category).'">'
                        .'<span class="juvenil-lancamento-table__popover-category-icon">'.$categoryIcon.'</span>'
                    .'</span>'
                    .'<div class="juvenil-lancamento-table__popover-main">'
                        .'<span class="juvenil-lancamento-table__popover-kicker">'.e($type).' #'.e((string) $item->registration_id).'</span>'
                        .'<strong>'.e($name).'</strong>'
                        .'<span>'.e($itemName).'</span>'
                    .'</div>'
                    .'<div class="juvenil-lancamento-table__popover-side">'
                        .'<span class="juvenil-lancamento-table__popover-category-name">'.e($category).'</span>'
                        .'<strong>'.e($amount).'</strong>'
                    .'</div>'
                .'</div>';
            })
            ->implode('');

        $popover = '<span class="juvenil-lancamento-table__popover" role="tooltip">'
            .'<span class="juvenil-lancamento-table__popover-title">Itens deste lançamento</span>'
            .$popoverItems
        .'</span>';

        return new HtmlString('<span class="juvenil-lancamento-table__registrations" tabindex="0">'.$visibleItems.$popover.'</span>');
    }
}
