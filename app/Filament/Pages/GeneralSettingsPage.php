<?php

namespace App\Filament\Pages;

use App\Enums\LiberacaoInscricoesEquipeTrabalhoStatusEnum;
use App\Enums\LiberacaoInscricoesStatusEnum;
use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\SettingsPage;
use FilamentTiptapEditor\Enums\TiptapOutput;
use FilamentTiptapEditor\TiptapEditor;
use Leandrocfe\FilamentPtbrFormFields\PhoneNumber;

class GeneralSettingsPage extends SettingsPage
{
    use HasPageShield;

    protected static ?string $title = 'Configurações Gerais';
    protected static ?int $navigationSort = 99;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Configurações';

    protected static string $settings = GeneralSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns(12)
                    ->schema([
                        Section::make('Contato do Atendente')
                            ->schema([
                                PhoneNumber::make('telefone_atendente')
                                ->required()
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
                                    ->prefixIcon(fn($state) => LiberacaoInscricoesStatusEnum::tryFrom($state)->getIcon())
                                    ->prefixIconColor(fn($state) => LiberacaoInscricoesStatusEnum::tryFrom($state)->getColor())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                        'lg' => '4'
                                    ]),

                                Select::make('liberacao_inscricoes_status')
                                    ->label('Status das Inscrições Campistas')
                                    ->options(LiberacaoInscricoesStatusEnum::class)
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->live()
                                    ->prefixIcon(fn($state) => LiberacaoInscricoesStatusEnum::tryFrom($state)->getIcon())
                                    ->prefixIconColor(fn($state) => LiberacaoInscricoesStatusEnum::tryFrom($state)->getColor())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => '6',
                                        'lg' => '4'
                                    ]),

                                TiptapEditor::make('liberacao_inscricoes_bloco')
                                    ->label('Conteúdo bloco de inscrições dos campistas')
                                    ->hint('O conteúdo do bloco de inscrições aparece quando o status das inscrições estiver
                                        diferente de ' . LiberacaoInscricoesStatusEnum::LIBERADO->getLabel())
                                    ->hintIcon('heroicon-o-information-circle')
                                    ->hintColor('warning')
                                    ->profile('default')
                                    ->columnSpanFull()
                                    ->directory('settings') // optional, defaults to config setting
                                    ->maxSize(1024) // optional, defaults to config setting
                                    ->output(TiptapOutput::Html) // optional, change the format for saved data, default is html
                                    ->maxContentWidth('5xl')
                                    ->required(fn(Get $get) => LiberacaoInscricoesStatusEnum::tryFrom($get('liberacao_inscricoes_status')) !== LiberacaoInscricoesStatusEnum::LIBERADO)
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
