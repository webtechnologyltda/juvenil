<?php

namespace App\Filament\Pages;

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Leandrocfe\FilamentPtbrFormFields\PhoneNumber;

class GeneralSettingsPage extends SettingsPage
{
    use HasPageShield;

    protected static ?string $title = 'Configurações Gerais';

    protected static ?int $navigationSort = 99;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Configurações';

    protected static string $settings = GeneralSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columns(12)
                    ->schema([
                        Section::make('Contato do Atendente')
                            ->schema([
                                PhoneNumber::make('telefone_atendente')
                                    ->required(),
                            ])
                            ->columnSpan([
                                'default' => 'full',
                                'md' => '6',
                                'lg' => '3',
                            ])
                            ->icon('heroicon-o-phone'),

                        Section::make('Controle de Vagas')
                            ->schema([
                                Select::make('liberacao_inscricoes_equipe_trabalho_status')
                                    ->label('Status das Inscrições Equipe de Trabalho')
                                    ->options(LiberacaoInscricoesEquipeTrabalhoStatusEnum::class)
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->prefixIcon(fn ($state) => LiberacaoInscricoesStatusEnum::tryFrom($state)->getIcon())
                                    ->prefixIconColor(fn ($state) => LiberacaoInscricoesStatusEnum::tryFrom($state)->getColor())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                        'lg' => '4',
                                    ]),

                                Select::make('liberacao_inscricoes_status')
                                    ->label('Status das Inscrições Campistas')
                                    ->options(LiberacaoInscricoesStatusEnum::class)
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->prefixIcon(fn ($state) => LiberacaoInscricoesStatusEnum::tryFrom($state)->getIcon())
                                    ->prefixIconColor(fn ($state) => LiberacaoInscricoesStatusEnum::tryFrom($state)->getColor())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                        'lg' => '4',
                                    ]),

                                RichEditor::make('liberacao_inscricoes_bloco')
                                    ->label('Conteúdo bloco de inscrições dos campistas')
                                    ->hint('O conteúdo do bloco de inscrições aparece quando o status das inscrições estiver
                                        diferente de '.LiberacaoInscricoesStatusEnum::LIBERADO->getLabel())
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintColor('warning')
                                    ->columnSpanFull()
                                    ->fileAttachmentsDirectory('settings')
                                    ->fileAttachmentsMaxSize(1024)
                                    ->required(fn (Get $get) => LiberacaoInscricoesStatusEnum::tryFrom($get('liberacao_inscricoes_status')) !== LiberacaoInscricoesStatusEnum::LIBERADO),
                            ])
                            ->columnSpan([
                                'default' => 'full',
                                'md' => '6',
                                'lg' => '9',
                            ])
                            ->columns(12)
                            ->icon('tabler-lock'),
                    ]),
            ]);
    }
}
