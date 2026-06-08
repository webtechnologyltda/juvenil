<?php

namespace App\Filament\Resources\LancamentoResource\Forms;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\CategoriaLancamento;
use App\Models\Lancamento;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use App\Support\IconBadge;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\RawJs;
use Leandrocfe\FilamentPtbrFormFields\Money;

abstract class LancamentoForm
{
    private const COMPROVANTE_BLOCK = 'anexar_comprovante';

    public static function getFormSchema(): array
    {
        return [
            Grid::make([
                'default' => 1,
                'lg' => 12,
            ])
                ->schema([
                    Section::make('Lançamento')
                        ->description('Controle financeiro do acampamento')
                        ->columns([
                            'default' => 1,
                            'md' => 2,
                            'xl' => 12,
                        ])
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 12,
                        ])
                        ->schema([

                            TextInput::make('nome')
                                ->label('Nome')
                                ->required()
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 4,
                                ]),

                            Money::make('valor')
                                ->label('Valor')
                                ->columns(1)
                                ->intFormat()
                                ->prefix(RawJs::make('R$'))
                                ->required()
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 2,
                                ]),

                            DatePicker::make('data')
                                ->label('Data de Lançamento')
                                ->required()
                                ->format('Y-m-d')
                                ->displayFormat('d/m/Y')
                                ->default(Carbon::now()->format('Y-m-d'))
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 2,
                                ]),

                            Select::make('status')
                                ->label('Status')
                                ->options(StatusLacamento::class)
                                ->searchable()
                                ->required()
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 2,
                                ]),

                            Select::make('forma_pagamento')
                                ->label('Forma de Pagamento')
                                ->options(FormaPagamento::class)
                                ->searchable()
                                ->required()
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 2,
                                ]),

                            ToggleButtons::make('tipo')
                                ->label('Tipo de Lançamento')
                                ->options(TipoLacamento::class)
                                ->inline()
                                ->live()
                                ->afterStateUpdated(fn (Set $set): mixed => $set('categoria_lancamento_id', null))
                                ->required()
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 2,
                                    'xl' => 4,
                                ]),

                            Select::make('categoria_lancamento_id')
                                ->label('Categoria')
                                ->options(fn (Get $get): array => self::categoryOptions($get('tipo')))
                                ->allowHtml()
                                ->searchable()
                                ->preload()
                                ->placeholder('Selecione uma categoria')
                                ->helperText('As opções acompanham o tipo do lançamento.')
                                ->disabled(fn (Get $get): bool => blank($get('tipo')))
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 4,
                                ]),

                            TextInput::make('comprador')
                                ->label('Comprador')
                                ->required()
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 4,
                                ]),

                            Textarea::make('descricao')
                                ->label('Descrição')
                                ->rows(3)
                                ->required()
                                ->columnSpanFull(),
                        ]),

                    Section::make('Inscrições vinculadas')
                        ->description('Aplique este lançamento em uma ou mais inscrições.')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 8,
                        ])
                        ->schema([
                            Repeater::make('registration_payments')
                                ->label('Pagamentos de inscrições')
                                ->helperText('A soma dos valores aplicados não pode ultrapassar o valor total do lançamento.')
                                ->schema([
                                    Select::make('registration_type')
                                        ->label('Tipo da inscrição')
                                        ->options(RegistrationPaymentAllocator::registrationTypeOptions())
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function (Set $set): void {
                                            $set('registration_id', null);
                                            $set('amount', null);
                                        })
                                        ->required()
                                        ->columnSpan([
                                            'default' => 'full',
                                            'lg' => 3,
                                        ]),

                                    Select::make('registration_id')
                                        ->label('Inscrição')
                                        ->options(fn (Get $get, ?Lancamento $record): array => app(RegistrationPaymentAllocator::class)
                                            ->registrationOptions(
                                                $get('registration_type'),
                                                $record?->id,
                                                filled($get('registration_id')) ? (int) $get('registration_id') : null,
                                            ))
                                        ->searchable()
                                        ->preload()
                                        ->disabled(fn (Get $get): bool => blank($get('registration_type')))
                                        ->required()
                                        ->columnSpan([
                                            'default' => 'full',
                                            'lg' => 6,
                                        ]),

                                    Money::make('amount')
                                        ->label('Valor aplicado')
                                        ->intFormat()
                                        ->prefix(RawJs::make('R$'))
                                        ->required()
                                        ->columnSpan([
                                            'default' => 'full',
                                            'lg' => 3,
                                        ]),
                                ])
                                ->columns(12)
                                ->defaultItems(0)
                                ->addActionLabel('Adicionar inscrição')
                                ->reorderable(false)
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),

                    Section::make('Comprovantes')
                        ->description('Anexe recibos, PIX e demais documentos do lançamento.')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 4,
                        ])
                        ->schema([
                            Repeater::make('comprovante')
                                ->label('Comprovantes')
                                ->required()
                                ->afterStateHydrated(static function (Repeater $component): void {
                                    $component->rawState(self::comprovanteRepeaterFormState($component->getRawState()));
                                    $component->hydrateItems();
                                })
                                ->addActionLabel('Adicionar comprovante')
                                ->itemLabel('Comprovante')
                                ->schema([
                                    FileUpload::make('url')
                                        ->placeholder('Tamanho max.: 2MB')
                                        ->hint('Tamanho máximo: 2MB')
                                        ->label('Documento')
                                        ->required()
                                        ->downloadable()
                                        ->openable()
                                        ->multiple()
                                        ->maxSize(2048)
                                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                                        ->previewable(true)
                                        ->columnSpanFull(),
                                    Textarea::make('observacao')
                                        ->label('Observação')
                                        ->placeholder('Opcional: informe algum detalhe sobre este comprovante.')
                                        ->rows(3)
                                        ->maxLength(1000)
                                        ->columnSpanFull(),
                                ])
                                ->defaultItems(1)
                                ->reorderableWithButtons()
                                ->collapsible()
                                ->columnSpanFull(),
                        ]),

                ]),
        ];
    }

    /**
     * @return array<int, array{registration_type: string, registration_id: int, amount: int}>
     */
    public static function registrationPaymentsFormState(Lancamento $lancamento): array
    {
        return $lancamento->registrationPayments()
            ->orderBy('id')
            ->get(['registration_type', 'registration_id', 'amount'])
            ->map(fn ($payment): array => [
                'registration_type' => $payment->registration_type,
                'registration_id' => (int) $payment->registration_id,
                'amount' => (int) $payment->amount,
            ])
            ->all();
    }

    public static function normalizeComprovanteState(mixed $state): array
    {
        if (blank($state)) {
            return [];
        }

        if (is_string($state)) {
            return [self::makeComprovanteBlock(files: [$state])];
        }

        if (! is_array($state)) {
            return [];
        }

        if (array_is_list($state) && self::containsOnlyFiles($state)) {
            return [self::makeComprovanteBlock(files: $state)];
        }

        $items = [];

        foreach ($state as $item) {
            if (is_string($item)) {
                $items[] = self::makeComprovanteBlock(files: [$item]);

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            if (array_key_exists('type', $item)) {
                $data = is_array($item['data'] ?? null) ? $item['data'] : [];
                $legacyName = $data['comprovante_nome'] ?? null;
                $data['url'] = self::normalizeFileUploadState($data['url'] ?? []);
                $data['observacao'] = self::normalizeObservation($data['observacao'] ?? $legacyName);
                unset($data['comprovante_nome']);

                $items[] = [
                    'type' => filled($item['type'] ?? null) ? $item['type'] : self::COMPROVANTE_BLOCK,
                    'data' => $data,
                ];

                continue;
            }

            if (array_key_exists('url', $item) || array_key_exists('observacao', $item) || array_key_exists('comprovante_nome', $item)) {
                $items[] = self::makeComprovanteBlock(
                    files: self::normalizeFileUploadState($item['url'] ?? []),
                    observation: $item['observacao'] ?? $item['comprovante_nome'] ?? null,
                );
            }
        }

        return $items;
    }

    /**
     * @return array<int, array{url: array<int, string>, observacao: string|null}>
     */
    public static function comprovanteRepeaterFormState(mixed $state): array
    {
        return array_map(
            static function (array $item): array {
                $data = is_array($item['data'] ?? null) ? $item['data'] : [];

                return [
                    'url' => self::normalizeFileUploadState($data['url'] ?? []),
                    'observacao' => self::normalizeObservation($data['observacao'] ?? null),
                ];
            },
            self::normalizeComprovanteState($state),
        );
    }

    private static function makeComprovanteBlock(array $files = [], ?string $observation = null): array
    {
        return [
            'type' => self::COMPROVANTE_BLOCK,
            'data' => [
                'url' => self::normalizeFileUploadState($files),
                'observacao' => self::normalizeObservation($observation),
            ],
        ];
    }

    private static function normalizeFileUploadState(mixed $files): array
    {
        if (blank($files)) {
            return [];
        }

        if (is_string($files)) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, fn (mixed $file): bool => is_string($file) && filled($file)));
    }

    private static function normalizeObservation(mixed $observation): ?string
    {
        if (! is_string($observation)) {
            return null;
        }

        $observation = trim($observation);

        return $observation === '' ? null : $observation;
    }

    private static function containsOnlyFiles(array $items): bool
    {
        if ($items === []) {
            return false;
        }

        foreach ($items as $item) {
            if (! is_string($item)) {
                return false;
            }
        }

        return true;
    }

    private static function categoryOptions(mixed $type): array
    {
        if ($type instanceof TipoLacamento) {
            $type = $type->value;
        }

        if (blank($type)) {
            return [];
        }

        return CategoriaLancamento::query()
            ->where('ativo', true)
            ->where('tipo', (int) $type)
            ->orderBy('nome')
            ->get()
            ->mapWithKeys(fn (CategoriaLancamento $category): array => [
                $category->id => (string) IconBadge::tile($category, $category->nome, fallbackIcon: 'heroicon-o-tag'),
            ])
            ->all();
    }
}
