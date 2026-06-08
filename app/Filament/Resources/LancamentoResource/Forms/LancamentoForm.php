<?php

namespace App\Filament\Resources\LancamentoResource\Forms;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource\Tables\LancamentoItemCampistasTable;
use App\Filament\Resources\LancamentoResource\Tables\LancamentoItemEquipeTrabalhoTable;
use App\Models\CategoriaLancamento;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Support\EnumOptionBadge;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use App\Support\IconBadge;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
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

                            DatePicker::make('data')
                                ->label('Data de Lançamento')
                                ->required()
                                ->format('Y-m-d')
                                ->displayFormat('d/m/Y')
                                ->default(fn (): string => now()->toDateString())
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 2,
                                ]),

                            Select::make('status')
                                ->label('Status')
                                ->options(fn (): array => EnumOptionBadge::options(StatusLacamento::class))
                                ->allowHtml()
                                ->enum(StatusLacamento::class)
                                ->searchable()
                                ->required()
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 2,
                                ]),

                            Select::make('forma_pagamento')
                                ->label('Forma de Pagamento')
                                ->options(fn (): array => EnumOptionBadge::options(FormaPagamento::class))
                                ->allowHtml()
                                ->enum(FormaPagamento::class)
                                ->searchable()
                                ->live()
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
                                ->afterStateUpdated(static function (Set $set, mixed $state): void {
                                    $set('items', []);

                                    if (! self::isExpenseType($state)) {
                                        $set('comprador', null);
                                    }
                                })
                                ->required()
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 2,
                                    'xl' => 4,
                                ]),

                            TextInput::make('comprador')
                                ->label('Comprador')
                                ->required(fn (Get $get): bool => self::isExpenseType($get('tipo')))
                                ->visible(fn (Get $get): bool => self::isExpenseType($get('tipo')))
                                ->dehydrated(fn (Get $get): bool => self::isExpenseType($get('tipo')))
                                ->maxLength(255)
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 4,
                                ]),

                            RichEditor::make('descricao')
                                ->label('Descrição')
                                ->toolbarButtons([['bold', 'italic', 'underline'], ['bulletList', 'orderedList'], ['link', 'clearFormatting']])
                                ->columnSpanFull(),
                        ]),

                    Section::make('Itens do lançamento')
                        ->description('Classifique valores, categorias e vínculos financeiros.')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 8,
                        ])
                        ->schema([
                            Repeater::make('items')
                                ->label('Itens')
                                ->schema([
                                    TextInput::make('nome')
                                        ->label('Nome')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 1,
                                            'xl' => 5,
                                        ]),

                                    Money::make('valor')
                                        ->label('Valor')
                                        ->intFormat()
                                        ->prefix(RawJs::make('R$'))
                                        ->required()
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 1,
                                            'xl' => 2,
                                        ]),

                                    Select::make('categoria_lancamento_id')
                                        ->label('Categoria')
                                        ->options(fn (Get $get): array => self::categoryOptions($get('../../tipo')))
                                        ->allowHtml()
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->disabled(fn (Get $get): bool => blank($get('../../tipo')))
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 1,
                                            'xl' => 5,
                                        ]),

                                    Select::make('registration_type')
                                        ->label('Tipo da inscrição')
                                        ->options(RegistrationPaymentAllocator::registrationTypeOptions())
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function (Set $set): void {
                                            $set('registration_id', null);
                                        })
                                        ->placeholder('Sem vínculo')
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 1,
                                            'xl' => 3,
                                        ]),

                                    ModalTableSelect::make('registration_id')
                                        ->label('Inscrição')
                                        ->tableConfiguration(fn (Get $get): string => $get('registration_type') === EquipeTrabalho::class
                                            ? LancamentoItemEquipeTrabalhoTable::class
                                            : LancamentoItemCampistasTable::class)
                                        ->tableArguments(fn (Get $get, ?Lancamento $record): array => [
                                            'excluding_lancamento_id' => $record?->id,
                                            'current_registration_id' => filled($get('registration_id')) ? (int) $get('registration_id') : null,
                                        ])
                                        ->getOptionLabelUsing(fn (Get $get, ?Lancamento $record, mixed $value): ?string => self::registrationOptionLabel(
                                            $get('registration_type'),
                                            $value,
                                            $record?->id,
                                        ))
                                        ->selectAction(fn (Action $action): Action => $action
                                            ->label('Selecionar inscrição')
                                            ->modalHeading('Selecionar inscrição para o item')
                                            ->modalSubmitActionLabel('Aplicar inscrição')
                                            ->modalWidth(Width::SevenExtraLarge)
                                            ->slideOver()
                                            ->icon('heroicon-o-magnifying-glass'))
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                                            $name = self::registrationName($get('registration_type'), $state);

                                            if ($name !== null) {
                                                $set('nome', $name);
                                            }
                                        })
                                        ->disabled(fn (Get $get): bool => blank($get('registration_type')))
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 2,
                                            'xl' => 9,
                                        ]),

                                    Textarea::make('descricao')
                                        ->label('Descrição')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                ])
                                ->columns([
                                    'default' => 1,
                                    'md' => 2,
                                    'xl' => 12,
                                ])
                                ->defaultItems(1)
                                ->minItems(1)
                                ->addActionLabel('Adicionar item')
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
                                ->required(fn (Get $get): bool => self::requiresComprovante($get('status'), $get('tipo'), $get('forma_pagamento')))
                                ->minItems(fn (Get $get): int => self::requiresComprovante($get('status'), $get('tipo'), $get('forma_pagamento')) ? 1 : 0)
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
                                        ->required(fn (Get $get): bool => self::requiresComprovante($get('../../status'), $get('../../tipo'), $get('../../forma_pagamento')))
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
     * @return array<int, array{nome: string, valor: int, categoria_lancamento_id: int, registration_type: ?string, registration_id: ?int, descricao: ?string}>
     */
    public static function itemsFormState(Lancamento $lancamento): array
    {
        return $lancamento->items()
            ->orderBy('id')
            ->get(['nome', 'valor', 'categoria_lancamento_id', 'registration_type', 'registration_id', 'descricao'])
            ->map(fn ($item): array => [
                'nome' => $item->nome,
                'valor' => (int) $item->valor,
                'categoria_lancamento_id' => (int) $item->categoria_lancamento_id,
                'registration_type' => $item->registration_type,
                'registration_id' => $item->registration_id ? (int) $item->registration_id : null,
                'descricao' => $item->descricao,
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
                $items[] = self::makeFilledComprovanteBlock(files: [$item]);

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

                $block = [
                    'type' => filled($item['type'] ?? null) ? $item['type'] : self::COMPROVANTE_BLOCK,
                    'data' => $data,
                ];

                if (self::filledComprovanteBlock($block)) {
                    $items[] = $block;
                }

                continue;
            }

            if (array_key_exists('url', $item) || array_key_exists('observacao', $item) || array_key_exists('comprovante_nome', $item)) {
                $block = self::makeFilledComprovanteBlock(
                    files: self::normalizeFileUploadState($item['url'] ?? []),
                    observation: $item['observacao'] ?? $item['comprovante_nome'] ?? null,
                );

                if ($block !== null) {
                    $items[] = $block;
                }
            }
        }

        return $items;
    }

    public static function normalizeCompradorForType(array $data): array
    {
        if (! self::isExpenseType($data['tipo'] ?? null)) {
            $data['comprador'] = null;
        }

        return $data;
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

    private static function makeFilledComprovanteBlock(array $files = [], ?string $observation = null): ?array
    {
        $block = self::makeComprovanteBlock($files, $observation);

        return self::filledComprovanteBlock($block) ? $block : null;
    }

    private static function filledComprovanteBlock(array $block): bool
    {
        return filled(data_get($block, 'data.url')) || filled(data_get($block, 'data.observacao'));
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

    private static function isExpenseType(mixed $type): bool
    {
        if ($type instanceof TipoLacamento) {
            return $type === TipoLacamento::Despesa;
        }

        if (blank($type)) {
            return false;
        }

        return (int) $type === TipoLacamento::Despesa->value;
    }

    private static function requiresComprovante(mixed $status, mixed $type, mixed $paymentMethod): bool
    {
        if (! self::isPaidStatus($status)) {
            return false;
        }

        return ! (self::isRevenueType($type) && self::isCashPayment($paymentMethod));
    }

    private static function isPaidStatus(mixed $status): bool
    {
        if ($status instanceof StatusLacamento) {
            return $status === StatusLacamento::Pago;
        }

        if (blank($status)) {
            return false;
        }

        return (int) $status === StatusLacamento::Pago->value;
    }

    private static function isRevenueType(mixed $type): bool
    {
        if ($type instanceof TipoLacamento) {
            return $type === TipoLacamento::Receita;
        }

        if (blank($type)) {
            return false;
        }

        return (int) $type === TipoLacamento::Receita->value;
    }

    private static function isCashPayment(mixed $paymentMethod): bool
    {
        if ($paymentMethod instanceof FormaPagamento) {
            return $paymentMethod === FormaPagamento::Dinheiro;
        }

        if (blank($paymentMethod)) {
            return false;
        }

        return (int) $paymentMethod === FormaPagamento::Dinheiro->value;
    }

    private static function registrationName(?string $registrationType, mixed $registrationId): ?string
    {
        if (blank($registrationType) || blank($registrationId)) {
            return null;
        }

        if (! array_key_exists($registrationType, RegistrationPaymentAllocator::registrationTypeOptions())) {
            return null;
        }

        $registration = $registrationType::query()->find($registrationId);

        return $registration?->getAttribute('nome');
    }

    private static function registrationOptionLabel(?string $registrationType, mixed $registrationId, ?int $excludingLancamentoId = null): ?string
    {
        if (blank($registrationType) || blank($registrationId)) {
            return null;
        }

        $registrationId = (int) $registrationId;

        if ($registrationId <= 0) {
            return null;
        }

        return app(RegistrationPaymentAllocator::class)
            ->registrationOptions($registrationType, $excludingLancamentoId, $registrationId)[$registrationId]
            ?? self::registrationName($registrationType, $registrationId);
    }

    public static function categoryOptions(mixed $type): array
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
