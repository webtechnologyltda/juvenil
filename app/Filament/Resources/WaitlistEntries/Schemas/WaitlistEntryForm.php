<?php

namespace App\Filament\Resources\WaitlistEntries\Schemas;

use App\Enums\WaitlistEntryStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;

class WaitlistEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'lg' => 12,
            ])
            ->components([
                Section::make('Dados pessoais')
                    ->description('Identificação e contato da pessoa na fila.')
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
                        TextInput::make('nome')
                            ->label('Nome completo')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 7,
                            ]),

                        TextInput::make('telefone')
                            ->label('WhatsApp')
                            ->required()
                            ->maxLength(32)
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 5,
                            ]),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255)
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 7,
                            ]),

                        DatePicker::make('data_nascimento')
                            ->label('Data de nascimento')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 5,
                            ]),

                        ToggleButtons::make('sexo')
                            ->label('Sexo')
                            ->required()
                            ->inline()
                            ->options([
                                'M' => 'Masculino',
                                'F' => 'Feminino',
                            ])
                            ->colors([
                                'M' => Color::Blue,
                                'F' => Color::Pink,
                            ])
                            ->icons([
                                'M' => 'eos-male',
                                'F' => 'eos-female',
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Controle da fila')
                    ->description('Status usado para acompanhar a convocação.')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(1)
                    ->columnSpan([
                        'default' => 'full',
                        'lg' => 5,
                    ])
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(WaitlistEntryStatus::class)
                            ->required()
                            ->native(false),
                    ]),

                Section::make('Observações')
                    ->description('Anotações visíveis no contexto público e observações internas da coordenação.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->columns([
                        'default' => 1,
                        'lg' => 2,
                    ])
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('observacao')
                            ->label('Observação pública')
                            ->rows(4)
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 1,
                            ]),

                        Textarea::make('admin_notes')
                            ->label('Observação interna')
                            ->rows(4)
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 1,
                            ]),
                    ]),
            ]);
    }
}
