<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnum;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use STS\FilamentImpersonate\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?string $label = 'Usuário';

    protected static ?string $pluralLabel = 'Usuários';

    protected static ?string $slug = 'users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 12])->schema([
                    Section::make()
                        ->columnSpan([
                            'default' => 12,
                            'lg' => 9,
                        ])
                        ->schema([
                            Grid::make(['default' => 12])->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->rules(['max:255', 'string'])
                                    ->placeholder('Nome')
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 12,
                                        'lg' => 6,
                                    ]),

                                TextInput::make('email')
                                    ->required()
                                    ->email()
                                    ->unique(
                                        'users',
                                        'email',
                                        fn (?Model $record) => $record
                                    )
                                    ->placeholder('Email')
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 12,
                                        'lg' => 6,
                                    ]),

                                TextInput::make('password')
                                    ->password()
                                    ->hidden(fn (string $context): bool => $context !== 'create')
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->placeholder('Senha')
                                    ->rule('confirmed')
                                    ->label('Senha')
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 12,
                                        'lg' => 4,
                                    ]),

                                TextInput::make('password_confirmation')
                                    ->hidden(fn (string $context): bool => $context !== 'create')
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->placeholder('Confirmação de Senha')
                                    ->label('Confirmação de Senha')
                                    ->password()
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 12,
                                        'lg' => 4,
                                    ]),
                            ]),
                        ]),
                    Section::make()
                        ->columnSpan([
                            'default' => 12,
                            'lg' => 3,
                        ])
                        ->schema([
                            Placeholder::make('Criado em:')
                                ->content(fn (?User $record) => is_null($record) ? '-' : $record->created_at->format('d/m/Y H:i:s'))
                                ->columnSpanFull(),
                            Placeholder::make('Última Atualização:')
                                ->content(fn (?User $record) => is_null($record) ? '-' : $record->updated_at->format('d/m/Y H:i:s'))
                                ->columnSpanFull(),
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->toggleable()
                    ->searchable()
                    ->sortable()
                    ->label('Nome'),
                Tables\Columns\TextColumn::make('email')
                    ->toggleable()
                    ->searchable()
                    ->label('E-mail'),
                Tables\Columns\TextColumn::make('roles.id')
                    ->formatStateUsing(fn (string $state): string => RoleEnum::getRoleEnumDescriptionById(intval($state)))
                    ->label('Perfis')
                    ->color(fn (string $state): string => match (RoleEnum::getRoleEnum(intval($state))) {
                        RoleEnum::SuperAdministrador => 'danger',
                        RoleEnum::Financeiro => 'warning',
                        RoleEnum::UsuarioComum => 'primary',
                        default => 'gray',
                    })
                    ->limitList(2)
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('Perfil')
                    ->label('Perfis')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('created_at')
                    ->label('Criado em')
                    ->form([
                        DatePicker::make('created_from')->label('Criado desde'),
                        DatePicker::make('created_until')->label('Criado até')->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Impersonate::make()
                    ->visible(fn () => auth()->user()->isSuperAdmin())
                    ->label('Simular acesso do usuário')
                    ->color(Color::Amber),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ])
            ->deferLoading()
            ->striped()
            ->paginated([5, 10, 15, 20]);
    }

    public static function getRelations(): array
    {
        return [
            UserResource\RelationManagers\RolesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
