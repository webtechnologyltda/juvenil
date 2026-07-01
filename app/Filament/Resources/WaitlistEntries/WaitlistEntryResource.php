<?php

namespace App\Filament\Resources\WaitlistEntries;

use App\Filament\Resources\WaitlistEntries\Pages\CreateWaitlistEntry;
use App\Filament\Resources\WaitlistEntries\Pages\EditWaitlistEntry;
use App\Filament\Resources\WaitlistEntries\Pages\ListWaitlistEntries;
use App\Filament\Resources\WaitlistEntries\Pages\ViewWaitlistEntry;
use App\Filament\Resources\WaitlistEntries\Schemas\WaitlistEntryForm;
use App\Filament\Resources\WaitlistEntries\Schemas\WaitlistEntryInfolist;
use App\Filament\Resources\WaitlistEntries\Tables\WaitlistEntriesTable;
use App\Models\WaitlistEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class WaitlistEntryResource extends Resource
{
    protected static ?string $model = WaitlistEntry::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão Acampamento';

    protected static ?string $label = 'Fila de espera';

    protected static ?string $pluralLabel = 'Fila de espera';

    public static function form(Schema $schema): Schema
    {
        return WaitlistEntryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return WaitlistEntryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WaitlistEntriesTable::configure($table);
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
            'index' => ListWaitlistEntries::route('/'),
            'create' => CreateWaitlistEntry::route('/create'),
            'view' => ViewWaitlistEntry::route('/{record}'),
            'edit' => EditWaitlistEntry::route('/{record}/edit'),
        ];
    }
}
