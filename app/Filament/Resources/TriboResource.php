<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TriboResource\Pages;
use App\Models\Tribo;
use App\Support\Tribes\TribeColor;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Tapp\FilamentAuditing\RelationManagers\AuditsRelationManager;

class TriboResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Tribo::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-flag';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Acampamento';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'lg' => 4,
            ])
            ->components([
                TextInput::make('cor')
                    ->label('Nome da tribo')
                    ->maxLength(255)
                    ->required()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 3,
                    ]),

                ColorPicker::make('cor_hex')
                    ->label('Cor da tribo')
                    ->default(TribeColor::FALLBACK)
                    ->required()
                    ->columnSpan([
                        'default' => 1,
                        'lg' => 1,
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Código'),
                TextColumn::make('cor')
                    ->label('Tribo'),
                TextColumn::make('cor_hex')
                    ->label('Cor')
                    ->formatStateUsing(fn (?string $state, Tribo $record): HtmlString => new HtmlString(sprintf(
                        '<span style="display:inline-flex;align-items:center;gap:.5rem;"><span style="width:1rem;height:1rem;border-radius:999px;border:1px solid rgba(148,163,184,.45);background:%s;"></span><span>%s</span></span>',
                        e(TribeColor::resolve($state, $record->cor)),
                        e(TribeColor::resolve($state, $record->cor)),
                    )))
                    ->html(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TriboResource\RelationManagers\CampistasRelationManager::class,
            TriboResource\RelationManagers\EquipeTrabalhosRelationManager::class,
            AuditsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTribos::route('/'),
            'create' => Pages\CreateTribo::route('/create'),
            'edit' => Pages\EditTribo::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'audit',
            'restoreAudit',
        ];
    }
}
