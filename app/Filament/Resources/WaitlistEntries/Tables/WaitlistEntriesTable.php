<?php

namespace App\Filament\Resources\WaitlistEntries\Tables;

use App\Enums\WaitlistEntryStatus;
use App\Models\WaitlistEntry;
use App\Support\Campistas\WaitlistManager;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\Validation\ValidationException;

class WaitlistEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('general_position')
                    ->label('Geral')
                    ->state(fn (WaitlistEntry $record): ?int => app(WaitlistManager::class)->generalPosition($record))
                    ->placeholder('-')
                    ->alignCenter(),

                TextColumn::make('sex_position')
                    ->label('Por sexo')
                    ->state(fn (WaitlistEntry $record): ?int => app(WaitlistManager::class)->sexPosition($record))
                    ->placeholder('-')
                    ->alignCenter(),

                TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telefone')
                    ->label('WhatsApp')
                    ->searchable(),

                TextColumn::make('sexo')
                    ->label('Sexo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'F' ? 'Feminino' : 'Masculino')
                    ->color(fn (?string $state): string => $state === 'F' ? 'pink' : 'blue'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('invitation_expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('sexo')
                    ->label('Sexo')
                    ->options([
                        'M' => 'Masculino',
                        'F' => 'Feminino',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(WaitlistEntryStatus::class),

                TernaryFilter::make('active_invitation')
                    ->label('Convite ativo')
                    ->queries(
                        true: fn ($query) => $query
                            ->where('status', WaitlistEntryStatus::Convocado->value)
                            ->where('invitation_expires_at', '>', now()),
                        false: fn ($query) => $query
                            ->where(function ($query): void {
                                $query
                                    ->where('status', '<>', WaitlistEntryStatus::Convocado->value)
                                    ->orWhereNull('invitation_expires_at')
                                    ->orWhere('invitation_expires_at', '<=', now());
                            }),
                    ),
            ])
            ->defaultSort('created_at')
            ->recordActions([
                Action::make('generateInvitation')
                    ->label('Gerar link')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->authorize('generateInvitation')
                    ->requiresConfirmation()
                    ->visible(fn (WaitlistEntry $record): bool => app(WaitlistManager::class)->canGenerateInvitation($record))
                    ->action(function (WaitlistEntry $record): void {
                        try {
                            $url = app(WaitlistManager::class)->generateInvitation($record, auth()->user());
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Convite não gerado')
                                ->body(collect($exception->errors())->flatten()->first())
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Link único gerado')
                            ->body(new HtmlString('<a href="'.e($url).'" target="_blank" class="font-bold underline">Abrir link de inscrição</a>'))
                            ->success()
                            ->send();
                    }),

                Action::make('sendInvitationLink')
                    ->label('Enviar link de inscrição')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->modalHeading('Enviar link de inscrição')
                    ->modalDescription('Escolha como deseja compartilhar o link único desta pessoa.')
                    ->modalContent(fn (WaitlistEntry $record): HtmlString => new HtmlString(
                        '<div class="space-y-3">'
                        .'<p class="text-sm font-medium text-white/75">Link de inscrição</p>'
                        .'<div class="break-all rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm font-medium text-gray-900 dark:border-white/10 dark:bg-white/5 dark:text-gray-100">'
                        .e(app(WaitlistManager::class)->invitationUrl($record))
                        .'</div>'
                        .'</div>',
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->extraModalFooterActions(fn (WaitlistEntry $record): array => [
                        Action::make('copyInvitationLink')
                            ->label('Copiar link')
                            ->icon('heroicon-o-clipboard-document')
                            ->color('gray')
                            ->alpineClickHandler('navigator.clipboard.writeText('.Js::from(app(WaitlistManager::class)->invitationUrl($record))->toHtml().'); close()'),

                        Action::make('sendInvitationWhatsapp')
                            ->label('Enviar via WhatsApp')
                            ->icon('fab-whatsapp')
                            ->color('success')
                            ->url(fn (): ?string => app(WaitlistManager::class)->whatsappUrl($record), shouldOpenInNewTab: true)
                            ->visible(fn (): bool => filled(app(WaitlistManager::class)->whatsappUrl($record))),
                    ])
                    ->visible(fn (WaitlistEntry $record): bool => filled($record->invitation_token_encrypted)
                        && $record->status === WaitlistEntryStatus::Convocado
                        && $record->invitation_expires_at?->isFuture()),

                Action::make('markWithdrawn')
                    ->label('Desistiu')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger')
                    ->authorize('update')
                    ->requiresConfirmation()
                    ->visible(fn (WaitlistEntry $record): bool => in_array($record->status, [WaitlistEntryStatus::Aguardando, WaitlistEntryStatus::Convocado], true))
                    ->action(fn (WaitlistEntry $record) => $record->update(['status' => WaitlistEntryStatus::Desistiu])),

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
