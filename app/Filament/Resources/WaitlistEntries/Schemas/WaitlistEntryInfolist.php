<?php

namespace App\Filament\Resources\WaitlistEntries\Schemas;

use App\Models\WaitlistEntry;
use App\Support\Campistas\WaitlistManager;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class WaitlistEntryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'lg' => 12,
            ])
            ->components([
                Section::make('Resumo da fila')
                    ->description('Posição, status e validade do convite.')
                    ->icon('heroicon-o-queue-list')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 12,
                    ])
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 1,
                                'xl' => 3,
                            ]),

                        TextEntry::make('general_position')
                            ->label('Posição geral')
                            ->state(fn (WaitlistEntry $record): ?int => app(WaitlistManager::class)->generalPosition($record))
                            ->placeholder('-')
                            ->badge()
                            ->color('info')
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 1,
                                'xl' => 3,
                            ]),

                        TextEntry::make('sex_position')
                            ->label('Posição por sexo')
                            ->state(fn (WaitlistEntry $record): ?int => app(WaitlistManager::class)->sexPosition($record))
                            ->placeholder('-')
                            ->badge()
                            ->color('info')
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 1,
                                'xl' => 3,
                            ]),

                        TextEntry::make('created_at')
                            ->label('Entrada na fila')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-')
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 1,
                                'xl' => 3,
                            ]),

                        TextEntry::make('invitation_generated_at')
                            ->label('Convite gerado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sem convite gerado')
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 1,
                                'xl' => 4,
                            ]),

                        TextEntry::make('invitation_expires_at')
                            ->label('Convite expira em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Sem convite ativo')
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 1,
                                'xl' => 4,
                            ]),

                        TextEntry::make('invitation_accepted_at')
                            ->label('Convite aceito em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Ainda não aceito')
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 1,
                                'xl' => 4,
                            ]),
                    ]),

                Section::make('Dados pessoais')
                    ->description('Identificação e contato deixados pela pessoa.')
                    ->icon('phosphor-user-list-fill')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 12,
                    ])
                    ->columnSpan([
                        'default' => 'full',
                        'lg' => 7,
                    ])
                    ->schema([
                        TextEntry::make('nome')
                            ->label('Nome completo')
                            ->weight(FontWeight::Bold)
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 6,
                            ]),

                        TextEntry::make('sexo')
                            ->label('Sexo')
                            ->formatStateUsing(fn (?string $state): string => $state === 'F' ? 'Feminino' : 'Masculino')
                            ->badge()
                            ->color(fn (?string $state): string => $state === 'F' ? 'pink' : 'blue')
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 3,
                            ]),

                        TextEntry::make('data_nascimento')
                            ->label('Nascimento')
                            ->date('d/m/Y')
                            ->placeholder('Não informado')
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 3,
                            ]),

                        TextEntry::make('telefone')
                            ->label('WhatsApp')
                            ->copyable()
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 6,
                            ]),

                        TextEntry::make('email')
                            ->label('E-mail')
                            ->copyable()
                            ->placeholder('Sem e-mail')
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 6,
                            ]),
                    ]),

                Section::make('Controle interno')
                    ->description('Acompanhamento operacional da fila.')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns([
                        'default' => 1,
                        'xl' => 2,
                    ])
                    ->columnSpan([
                        'default' => 'full',
                        'lg' => 5,
                    ])
                    ->schema([
                        TextEntry::make('campista.nome')
                            ->label('Inscrição vinculada')
                            ->placeholder('Ainda não gerou inscrição')
                            ->url(fn (WaitlistEntry $record): ?string => $record->campista_id === null
                                ? null
                                : route('filament.admin.resources.campistas.view', ['record' => $record->campista_id]))
                            ->openUrlInNewTab(false)
                            ->columnSpanFull(),

                        TextEntry::make('invitationGeneratedBy.name')
                            ->label('Convocado por')
                            ->placeholder('Não informado'),

                        TextEntry::make('cancelledBy.name')
                            ->label('Cancelado por')
                            ->placeholder('Não informado'),
                    ]),

                Section::make('Observações')
                    ->description('Anotações públicas e internas registradas na fila.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('observacao')
                            ->label('Observação pública')
                            ->placeholder('Sem observação')
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 1,
                            ]),

                        TextEntry::make('admin_notes')
                            ->label('Observação interna')
                            ->placeholder('Sem observação')
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 1,
                            ]),
                    ]),
            ]);
    }
}
