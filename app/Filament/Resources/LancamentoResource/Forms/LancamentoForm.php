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
use App\Support\Financeiro\MoneyAmount;
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
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

abstract class LancamentoForm
{
    private const COMPROVANTE_BLOCK = 'anexar_comprovante';

    private const CATEGORY_SEARCH_LIMIT = 50;

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
                                ->afterStateUpdated(static function (Set $set, Get $get): void {
                                    if (! is_array($get('items'))) {
                                        return;
                                    }

                                    foreach (array_keys($get('items')) as $itemKey) {
                                        $set("items.{$itemKey}.categoria_lancamento_id", null);
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

                            Html::make(fn (Get $get): HtmlString => self::itemsTotalHtml($get('items') ?? [], $get('tipo')))
                                ->columnSpan([
                                    'default' => 'full',
                                    'md' => 1,
                                    'xl' => 4,
                                ])
                                ->columnStart([
                                    'md' => 2,
                                    'xl' => 9,
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
                                            'xl' => 4,
                                        ]),

                                    TextInput::make('valor')
                                        ->label('Valor')
                                        ->prefix('R$')
                                        ->mask(RawJs::make(<<<'JS'
                                            (() => {
                                                const digits = $input.replace(/\D/g, '');
                                                const paddedLength = Math.max(digits.length, 3);
                                                const integerLength = Math.max(paddedLength - 2, 1);
                                                const integerMask = Array.from({ length: integerLength })
                                                    .fill('9')
                                                    .join('')
                                                    .replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                                                return `${integerMask},99`;
                                            })()
                                        JS))
                                        ->formatStateUsing(fn (mixed $state): string => MoneyAmount::formatForInput($state))
                                        ->dehydrateStateUsing(fn (mixed $state): int => MoneyAmount::toCents($state))
                                        ->inputMode('decimal')
                                        ->required()
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 1,
                                            'xl' => 4,
                                        ]),

                                    Select::make('categoria_lancamento_id')
                                        ->label('Categoria')
                                        ->allowHtml()
                                        ->searchable()
                                        ->getSearchResultsUsing(fn (Get $get, string $search): array => self::categorySearchResults($get('../../tipo'), $search))
                                        ->getOptionLabelUsing(fn (Get $get, mixed $value): ?string => self::categoryOptionLabel($get('../../tipo'), $value))
                                        ->required()
                                        ->disabled(fn (Get $get): bool => blank($get('../../tipo')))
                                        ->columnSpan([
                                            'default' => 'full',
                                            'md' => 1,
                                            'xl' => 4,
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
                                        ->visible(fn (Get $get): bool => ! self::isExpenseType($get('../../tipo')))
                                        ->dehydrated(fn (Get $get): bool => ! self::isExpenseType($get('../../tipo')))
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
                                        ->visible(fn (Get $get): bool => ! self::isExpenseType($get('../../tipo')))
                                        ->dehydrated(fn (Get $get): bool => ! self::isExpenseType($get('../../tipo')))
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

    public static function itemsTotalHtml(array $items, mixed $type): HtmlString
    {
        $total = self::signedItemsTotal($items, $type);
        $valueColor = self::itemsTotalValueColor($type);

        return new HtmlString(
            '<div role="status" data-lancamento-total x-data="'.e(self::itemsTotalAlpineData($total)).'" x-init="init()" class="flex w-fit min-w-72 flex-col items-start gap-1 rounded-lg bg-white/5 px-5 py-4">'
                .'<span class="text-sm font-semibold leading-none text-white">Total do lançamento</span>'
                .'<span x-text="formattedTotal" x-bind:style="valueStyle" class="text-[2.75rem] font-black leading-none tracking-normal" style="color: '.e($valueColor).';">'.e(self::formatCurrency($total)).'</span>'
            .'</div>',
        );
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

    private static function signedItemsTotal(array $items, mixed $type): int
    {
        $total = collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->sum(fn (array $item): int => MoneyAmount::toCents($item['valor'] ?? 0));

        return self::isExpenseType($type) ? $total * -1 : $total;
    }

    private static function itemsTotalAlpineData(int $initialTotal): string
    {
        $expenseType = TipoLacamento::Despesa->value;
        $typeColors = json_encode(self::itemsTotalTypeColors(), JSON_THROW_ON_ERROR);

        return sprintf(<<<'JS'
            {
                total: %d,
                expenseType: %d,
                typeColors: %s,
                observer: null,
                controller: null,
                refreshQueued: false,
                init() {
                    this.root = this.$root.closest('form') || document;
                    this.controller = new AbortController();
                    ['input', 'change', 'keyup', 'click'].forEach((eventName) => {
                        this.root.addEventListener(eventName, () => this.queueRefresh(), {
                            capture: true,
                            signal: this.controller.signal,
                        });
                    });
                    this.observer = new MutationObserver(() => this.queueRefresh());
                    this.observer.observe(this.root, {
                        childList: true,
                        subtree: true,
                    });
                    this.$nextTick(() => this.refresh());
                },
                destroy() {
                    this.controller?.abort();
                    this.observer?.disconnect();
                },
                queueRefresh() {
                    if (this.refreshQueued) {
                        return;
                    }

                    this.refreshQueued = true;

                    requestAnimationFrame(() => {
                        this.refreshQueued = false;
                        this.refresh();
                    });
                },
                refresh() {
                    const total = this.valueInputs()
                        .reduce((sum, input) => sum + this.toCents(input.value), 0);

                    this.total = this.isExpenseType() ? total * -1 : total;
                },
                valueInputs() {
                    return Array.from(this.root.querySelectorAll('input:not([type="hidden"])'))
                        .filter((input) => this.isFieldState(input, 'items', 'valor'));
                },
                isExpenseType() {
                    return Number.parseInt(this.typeValue(), 10) === this.expenseType;
                },
                typeValue() {
                    const candidates = Array.from(this.root.querySelectorAll('input, select'))
                        .filter((input) => this.isFieldState(input, null, 'tipo'));
                    const selected = candidates.find((input) => input.checked)
                        || candidates.find((input) => input.tagName === 'SELECT')
                        || candidates.find((input) => input.type === 'hidden');

                    return selected?.value ?? '';
                },
                isFieldState(input, parent, field) {
                    const statePath = [
                        input.getAttribute('name'),
                        input.getAttribute('wire:model'),
                        input.getAttribute('wire:model.live'),
                        input.getAttribute('wire:model.blur'),
                        input.getAttribute('wire:model.change'),
                    ].filter(Boolean).join(' ');

                    const hasField = statePath.endsWith(`[${field}]`)
                        || statePath.endsWith(`.${field}`)
                        || statePath.includes(`[${field}]`)
                        || statePath.includes(`.${field}`);

                    if (! hasField) {
                        return false;
                    }

                    return parent === null
                        || statePath.includes(`[${parent}]`)
                        || statePath.includes(`.${parent}.`);
                },
                toCents(value) {
                    const sanitized = String(value ?? '').replaceAll('R$', '').trim();

                    if (sanitized === '') {
                        return 0;
                    }

                    if (sanitized.includes(',')) {
                        const decimal = sanitized.replaceAll('.', '').replace(',', '.');

                        return Math.abs(Math.round((Number.parseFloat(decimal) || 0) * 100));
                    }

                    if (/^-?\\d+\\.\\d{1,2}$/.test(sanitized)) {
                        return Math.abs(Math.round((Number.parseFloat(sanitized) || 0) * 100));
                    }

                    return Math.abs(Number.parseInt(sanitized.replace(/\\D/g, ''), 10) || 0);
                },
                formatCurrency(value) {
                    const prefix = value < 0 ? '-R$ ' : 'R$ ';

                    return prefix + (Math.abs(value) / 100).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
                get formattedTotal() {
                    return this.formatCurrency(this.total);
                },
                get valueStyle() {
                    return `color: ${this.typeColors[this.typeValue()] || 'oklch(1 0 0)'}`;
                },
            }
        JS, $initialTotal, $expenseType, $typeColors);
    }

    private static function formatCurrency(int $amount): string
    {
        $prefix = $amount < 0 ? '-R$ ' : 'R$ ';

        return $prefix.number_format(abs($amount) / 100, 2, ',', '.');
    }

    /**
     * @return array<int, string>
     */
    private static function itemsTotalTypeColors(): array
    {
        return [
            TipoLacamento::Receita->value => Color::Green[400],
            TipoLacamento::Despesa->value => Color::Red[400],
            TipoLacamento::Doacao->value => Color::Blue[400],
        ];
    }

    private static function itemsTotalValueColor(mixed $type): string
    {
        if ($type instanceof TipoLacamento) {
            $type = $type->value;
        }

        if (blank($type)) {
            return 'oklch(1 0 0)';
        }

        return self::itemsTotalTypeColors()[(int) $type] ?? 'oklch(1 0 0)';
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
        return self::categorySearchResults($type);
    }

    public static function categorySearchResults(mixed $type, ?string $search = null): array
    {
        $query = self::categoryQuery($type);

        if (! $query) {
            return [];
        }

        if (filled($search)) {
            $query->where('nome', 'like', '%'.self::escapeLike((string) $search).'%');
        }

        return $query
            ->limit(self::CATEGORY_SEARCH_LIMIT)
            ->get()
            ->mapWithKeys(fn (CategoriaLancamento $category): array => [
                $category->id => self::categoryOptionHtml($category),
            ])
            ->all();
    }

    public static function categoryOptionLabel(mixed $type, mixed $value): ?string
    {
        $categoryId = (int) $value;

        if ($categoryId <= 0) {
            return null;
        }

        $query = self::categoryQuery($type);

        if (! $query) {
            return null;
        }

        $category = $query
            ->whereKey($categoryId)
            ->first();

        return $category ? self::categoryOptionHtml($category) : null;
    }

    private static function categoryQuery(mixed $type): ?Builder
    {
        if ($type instanceof TipoLacamento) {
            $type = $type->value;
        }

        if (blank($type)) {
            return null;
        }

        return CategoriaLancamento::query()
            ->where('ativo', true)
            ->where('tipo', (int) $type)
            ->orderBy('nome');
    }

    private static function categoryOptionHtml(CategoriaLancamento $category): string
    {
        return (string) IconBadge::tile($category, $category->nome, fallbackIcon: 'heroicon-o-tag');
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
