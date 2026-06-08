<?php

namespace App\Filament\Resources;

use App\Enums\TipoLacamento;
use App\Filament\Forms\Components\IconPicker;
use App\Filament\Resources\CategoriaLancamentoResource\Pages;
use App\Filament\Tables\Columns\ColoredIconColumn;
use App\Models\CategoriaLancamento;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriaLancamentoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = CategoriaLancamento::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Categoria de Lançamento';

    protected static ?string $pluralModelLabel = 'Categorias de Lançamento';

    protected static ?string $navigationLabel = 'Categorias de Lançamento';

    protected static ?string $recordTitleAttribute = 'nome';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'lg' => 2,
            ])
            ->components([
                Section::make('Identificação')
                    ->description('Como esta categoria aparece nos lançamentos.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 1,
                    ])
                    ->schema([
                        TextInput::make('nome')
                            ->label('Nome')
                            ->placeholder('Ex.: Inscrições')
                            ->required()
                            ->disabled(fn (?CategoriaLancamento $record): bool => $record?->isSystemDefault() ?? false)
                            ->dehydrated(fn (?CategoriaLancamento $record): bool => ! ($record?->isSystemDefault() ?? false))
                            ->maxLength(255),

                        ToggleButtons::make('tipo')
                            ->label('Tipo')
                            ->options(TipoLacamento::class)
                            ->inline()
                            ->grouped()
                            ->disabled(fn (?CategoriaLancamento $record): bool => $record?->isSystemDefault() ?? false)
                            ->dehydrated(fn (?CategoriaLancamento $record): bool => ! ($record?->isSystemDefault() ?? false))
                            ->required()
                            ->default(TipoLacamento::Receita),

                        Toggle::make('ativo')
                            ->label('Categoria ativa')
                            ->helperText('Categorias inativas continuam no histórico, mas deixam de aparecer como opção principal.')
                            ->disabled(fn (?CategoriaLancamento $record): bool => $record?->isSystemDefault() ?? false)
                            ->dehydrated(fn (?CategoriaLancamento $record): bool => ! ($record?->isSystemDefault() ?? false))
                            ->default(true),
                    ]),

                Section::make('Visual')
                    ->description('Cor e ícone usados na listagem e nos lançamentos.')
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 1,
                    ])
                    ->schema([
                        View::make('filament.app.components.icon-preview')
                            ->viewData(fn (Get $get): array => [
                                'currentIcon' => $get('icone'),
                                'currentColor' => $get('cor'),
                                'colorStatePath' => 'data.cor',
                            ]),

                        Grid::make(2)
                            ->schema([
                                ColorPicker::make('cor')
                                    ->label('Cor')
                                    ->hex()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->default('#f46b12'),

                                IconPicker::make('icone')
                                    ->label('Ícone')
                                    ->live()
                                    ->required()
                                    ->default('heroicon-o-tag'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                ColoredIconColumn::make('icone')
                    ->label('Ícone'),

                TextColumn::make('nome')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),

                TextColumn::make('lancamentos_count')
                    ->label('Lançamentos')
                    ->counts('lancamentos')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('ativo')
                    ->label('Ativa')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(TipoLacamento::class),
                TernaryFilter::make('ativo')
                    ->label('Ativa')
                    ->placeholder('Todas')
                    ->trueLabel('Ativas')
                    ->falseLabel('Inativas'),
            ])
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(
                fn (CategoriaLancamento $record): bool => ! $record->isSystemDefault(),
            )
            ->emptyStateHeading('Nenhuma categoria cadastrada')
            ->emptyStateDescription('Crie categorias para classificar receitas, despesas e doações do acampamento.')
            ->emptyStateIcon('heroicon-o-tag');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoriaLancamentos::route('/'),
            'create' => Pages\CreateCategoriaLancamento::route('/create'),
            'edit' => Pages\EditCategoriaLancamento::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }
}
