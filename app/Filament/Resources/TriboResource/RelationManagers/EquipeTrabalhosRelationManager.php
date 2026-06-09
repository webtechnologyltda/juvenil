<?php

namespace App\Filament\Resources\TriboResource\RelationManagers;

use App\Filament\Resources\EquipeTrabalhoResource;
use App\Models\EquipeTrabalho;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EquipeTrabalhosRelationManager extends RelationManager
{
    protected static string $relationship = 'equipeTrabalhos';

    protected static ?string $title = 'Equipe de trabalho da tribo';

    protected static ?string $label = 'Servo';

    protected static ?string $pluralLabel = 'Servos';

    protected static string|\BackedEnum|null $icon = 'ri-team-fill';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->equipeTrabalhos()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->heading('Equipe de trabalho da tribo')
            ->description('Servos e voluntários vinculados a esta tribo.')
            ->recordUrl(fn (EquipeTrabalho $record): string => EquipeTrabalhoResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->label('Cód.')
                    ->sortable()
                    ->visibleFrom('md'),

                ImageColumn::make('avatar_url')
                    ->state(fn (EquipeTrabalho $record): ?string => filter_var($record->avatar_url, FILTER_VALIDATE_URL) ? null : $record->avatar_url)
                    ->disk('public')
                    ->square()
                    ->alignCenter()
                    ->size(48)
                    ->grow(false)
                    ->defaultImageUrl(asset('img/logo.png'))
                    ->label('Foto'),

                TextColumn::make('nome')
                    ->label('Servo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('data_form.telefone')
                    ->label('Telefone')
                    ->visibleFrom('lg'),

                TextColumn::make('status')
                    ->badge()
                    ->alignCenter()
                    ->label('Status'),

                TextColumn::make('data_form.servir_no_acampamento')
                    ->label('Serve dentro')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state ? 'Sim' : 'Não')
                    ->color(fn (mixed $state): string => $state ? 'success' : 'gray')
                    ->alignCenter()
                    ->visibleFrom('md'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Action::make('view')
                    ->label('Visualizar servo')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Visualizar servo')
                    ->url(fn (EquipeTrabalho $record): string => EquipeTrabalhoResource::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Nenhum servo nesta tribo')
            ->emptyStateDescription('Servos aparecem aqui quando a inscrição da equipe de trabalho está vinculada a esta tribo.')
            ->defaultSort('nome');
    }
}
