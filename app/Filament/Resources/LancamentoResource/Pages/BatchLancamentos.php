<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Enums\TipoLacamento;
use App\Filament\Forms\Components\Money;
use App\Filament\Resources\LancamentoResource;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Filament\Resources\LancamentoResource\Tables\LancamentoBatchCampistasTable;
use App\Filament\Resources\LancamentoResource\Tables\LancamentoBatchEquipeTrabalhoTable;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Settings\GeneralSettings;
use App\Support\Financeiro\LancamentoBatchCreator;
use App\Support\Financeiro\MoneyAmount;
use App\Support\Financeiro\RegistrationPaymentAllocator;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ModalTableSelect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\RawJs;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class BatchLancamentos extends Page
{
    protected static string $resource = LancamentoResource::class;

    protected static ?string $title = 'Lançamentos em lote';

    protected static ?string $breadcrumb = 'Lote';

    protected string $view = 'filament.resources.lancamento-resource.pages.batch-lancamentos';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        abort_unless(LancamentoResource::canCreate(), 403);

        $this->form->fill($this->defaultFormData());
    }

    public function getTitle(): string|Htmlable
    {
        return 'Lançamentos em lote';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(1)
            ->components([
                Section::make('Configuração do lote')
                    ->description('Defina a origem e os dados comuns dos lançamentos pendentes.')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 12,
                    ])
                    ->schema([
                        ToggleButtons::make('mode')
                            ->label('Origem')
                            ->options([
                                LancamentoBatchCreator::MODE_REGISTRATIONS => 'Inscrições',
                                LancamentoBatchCreator::MODE_MANUAL => 'Avulso',
                            ])
                            ->inline()
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('registration_ids', []);
                                $set('registration_items', []);
                                $set('manual_items', [$this->blankManualItem()]);
                            })
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 4,
                            ]),

                        DatePicker::make('data')
                            ->label('Data dos lançamentos')
                            ->format('Y-m-d')
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->columnSpan([
                                'default' => 'full',
                                'md' => 1,
                                'xl' => 3,
                            ]),

                        Textarea::make('descricao')
                            ->label('Descrição padrão')
                            ->rows(2)
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 5,
                            ]),
                    ]),

                Section::make('Inscrições')
                    ->description('Selecione campistas ou equipe e ajuste os valores antes de criar o lote.')
                    ->icon('heroicon-o-users')
                    ->visible(fn (Get $get): bool => $get('mode') === LancamentoBatchCreator::MODE_REGISTRATIONS)
                    ->columns([
                        'default' => 1,
                        'lg' => 12,
                    ])
                    ->schema([
                        Select::make('registration_type')
                            ->label('Tipo de inscrição')
                            ->options(RegistrationPaymentAllocator::registrationTypeOptions())
                            ->native(false)
                            ->live()
                            ->required(fn (Get $get): bool => $get('mode') === LancamentoBatchCreator::MODE_REGISTRATIONS)
                            ->afterStateUpdated(function (Set $set): void {
                                $set('registration_ids', []);
                                $set('registration_items', []);
                            })
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 3,
                            ]),

                        Money::make('default_value')
                            ->label('Valor padrão')
                            ->intFormat()
                            ->prefix(RawJs::make('R$'))
                            ->visible(fn (Get $get): bool => $get('registration_type') !== EquipeTrabalho::class)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                if ($get('registration_type') === EquipeTrabalho::class) {
                                    return;
                                }

                                $amount = MoneyAmount::formatForInput($get('default_value'));
                                $items = collect($get('registration_items') ?? [])
                                    ->map(fn (array $item): array => [
                                        ...$item,
                                        'valor' => $amount,
                                    ])
                                    ->all();

                                $set('registration_items', $items);
                            })
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 3,
                            ]),

                        Html::make(fn (): HtmlString => new HtmlString($this->teamConfiguredAmountsHtml()))
                            ->visible(fn (Get $get): bool => $get('registration_type') === EquipeTrabalho::class)
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 3,
                            ]),

                        ModalTableSelect::make('registration_ids')
                            ->label('Inscrições')
                            ->placeholder('Nenhuma inscrição selecionada')
                            ->tableConfiguration(fn (Get $get): string => $get('registration_type') === EquipeTrabalho::class
                                ? LancamentoBatchEquipeTrabalhoTable::class
                                : LancamentoBatchCampistasTable::class)
                            ->getOptionLabelsUsing(fn (Get $get, array $values): array => $this->registrationOptionLabels($get('registration_type'), $values))
                            ->selectAction(fn (Action $action): Action => $action
                                ->label('Selecionar inscrições')
                                ->modalHeading('Selecionar inscrições para o lote')
                                ->modalSubmitActionLabel('Aplicar seleção')
                                ->modalWidth(Width::SevenExtraLarge)
                                ->slideOver(false)
                                ->icon('heroicon-o-magnifying-glass'))
                            ->multiple()
                            ->live()
                            ->disabled(fn (Get $get): bool => blank($get('registration_type')))
                            ->required(fn (Get $get): bool => $get('mode') === LancamentoBatchCreator::MODE_REGISTRATIONS)
                            ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                $set('registration_items', $this->registrationItemState(
                                    $get('registration_type'),
                                    is_array($state) ? $state : [],
                                    $get('default_value') ?? $this->defaultRegistrationAmount(),
                                ));
                            })
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 6,
                            ]),

                        Html::make(fn (Get $get): HtmlString => new HtmlString($this->registrationAmountWarningHtml($get('registration_type'))))
                            ->visible(fn (Get $get): bool => $this->registrationAmountNotConfigured($get('registration_type')))
                            ->columnSpanFull(),

                        Repeater::make('registration_items')
                            ->label('Valores por inscrição')
                            ->schema([
                                Hidden::make('registration_id'),

                                TextInput::make('nome')
                                    ->label('Nome')
                                    ->required()
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => 5,
                                    ]),

                                Money::make('valor')
                                    ->label('Valor')
                                    ->intFormat()
                                    ->prefix(RawJs::make('R$'))
                                    ->required()
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => 3,
                                    ]),

                                Textarea::make('descricao')
                                    ->label('Descrição do item')
                                    ->rows(2)
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => 4,
                                    ]),
                            ])
                            ->columns(12)
                            ->defaultItems(0)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])
                    ->footerActions([
                        Action::make('createBatch')
                            ->label('Criar lote pendente')
                            ->icon('heroicon-o-check-circle')
                            ->color('primary')
                            ->action(fn (): mixed => $this->createBatch()),
                    ])
                    ->footerActionsAlignment(Alignment::End),

                Section::make('Itens avulsos')
                    ->description('Crie lançamentos pendentes sem vínculo com inscrições.')
                    ->icon('heroicon-o-list-bullet')
                    ->visible(fn (Get $get): bool => $get('mode') === LancamentoBatchCreator::MODE_MANUAL)
                    ->columns([
                        'default' => 1,
                        'lg' => 12,
                    ])
                    ->schema([
                        ToggleButtons::make('tipo')
                            ->label('Tipo')
                            ->options(TipoLacamento::class)
                            ->inline()
                            ->live()
                            ->required(fn (Get $get): bool => $get('mode') === LancamentoBatchCreator::MODE_MANUAL)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $set('categoria_lancamento_id', null);
                                $set('manual_items', $this->manualItemsWithoutCategories($get('manual_items') ?? []));
                            })
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 4,
                            ]),

                        Select::make('categoria_lancamento_id')
                            ->label('Categoria padrão')
                            ->allowHtml()
                            ->searchable()
                            ->live()
                            ->preload()
                            ->options(fn (Get $get): array => LancamentoForm::categoryOptions($get('tipo')))
                            ->getSearchResultsUsing(fn (Get $get, string $search): array => LancamentoForm::categorySearchResults($get('tipo'), $search))
                            ->getOptionLabelUsing(fn (Get $get, mixed $value): ?string => LancamentoForm::categoryOptionLabel($get('tipo'), $value))
                            ->required(fn (Get $get): bool => $get('mode') === LancamentoBatchCreator::MODE_MANUAL)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $defaultValue = LancamentoForm::categoryDefaultValueForInput($get('tipo'), $get('categoria_lancamento_id'));
                                $items = collect($get('manual_items') ?? [])
                                    ->map(function (array $item) use ($defaultValue, $get): array {
                                        $item = [
                                            ...$item,
                                            'categoria_lancamento_id' => $get('categoria_lancamento_id'),
                                        ];

                                        if ($defaultValue !== null) {
                                            $item['valor'] = $defaultValue;
                                        }

                                        return $item;
                                    })
                                    ->all();

                                if ($defaultValue !== null) {
                                    $set('manual_default_value', $defaultValue);
                                }

                                $set('manual_items', $items);
                            })
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 4,
                            ]),

                        Money::make('manual_default_value')
                            ->label('Valor padrão avulso')
                            ->intFormat()
                            ->prefix(RawJs::make('R$'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $amount = MoneyAmount::formatForInput($get('manual_default_value'));
                                $items = collect($get('manual_items') ?? [])
                                    ->map(fn (array $item): array => [
                                        ...$item,
                                        'valor' => $amount,
                                    ])
                                    ->all();

                                $set('manual_items', $items);
                            })
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 4,
                            ]),

                        Repeater::make('manual_items')
                            ->label('Lançamentos')
                            ->schema([
                                TextInput::make('nome')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => 4,
                                    ]),

                                Money::make('valor')
                                    ->label('Valor')
                                    ->intFormat()
                                    ->prefix(RawJs::make('R$'))
                                    ->required()
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => 2,
                                    ]),

                                Select::make('categoria_lancamento_id')
                                    ->label('Categoria')
                                    ->allowHtml()
                                    ->searchable()
                                    ->live()
                                    ->preload()
                                    ->options(fn (Get $get): array => LancamentoForm::categoryOptions($get('../../tipo')))
                                    ->getSearchResultsUsing(fn (Get $get, string $search): array => LancamentoForm::categorySearchResults($get('../../tipo'), $search))
                                    ->getOptionLabelUsing(fn (Get $get, mixed $value): ?string => LancamentoForm::categoryOptionLabel($get('../../tipo'), $value))
                                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                        $defaultValue = LancamentoForm::categoryDefaultValueForInput($get('../../tipo'), $state);

                                        if ($defaultValue !== null) {
                                            $set('valor', $defaultValue);
                                        }
                                    })
                                    ->required()
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => 3,
                                    ]),

                                Textarea::make('descricao')
                                    ->label('Descrição')
                                    ->rows(2)
                                    ->columnSpan([
                                        'default' => 'full',
                                        'lg' => 3,
                                    ]),
                            ])
                            ->columns(12)
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel('Adicionar lançamento')
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])
                    ->footerActions([
                        Action::make('createBatch')
                            ->label('Criar lote pendente')
                            ->icon('heroicon-o-check-circle')
                            ->color('primary')
                            ->action(fn (): mixed => $this->createBatch()),
                    ])
                    ->footerActionsAlignment(Alignment::End),

                Section::make('Resumo')
                    ->description('Todos os lançamentos serão criados como pendentes e sem comprovantes.')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Html::make(fn (Get $get): HtmlString => new HtmlString($this->summaryHtml($get))),
                    ]),
            ]);
    }

    public function createBatch(): void
    {
        $data = $this->form->getState();

        if (($data['mode'] ?? null) === LancamentoBatchCreator::MODE_MANUAL) {
            $data['default_value'] = $data['manual_default_value'] ?? null;
        }

        $created = app(LancamentoBatchCreator::class)->create($data);
        $batchCode = $created->first()?->batch_code;

        Notification::make()
            ->title('Lote financeiro criado')
            ->body(sprintf('%s lançamento(s) pendente(s)%s.', $created->count(), $batchCode ? ' no lote '.$batchCode : ''))
            ->success()
            ->send();

        $this->form->fill($this->defaultFormData());
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultFormData(): array
    {
        return [
            'mode' => LancamentoBatchCreator::MODE_REGISTRATIONS,
            'registration_type' => Campista::class,
            'registration_ids' => [],
            'registration_items' => [],
            'default_value' => $this->defaultRegistrationAmount(),
            'tipo' => TipoLacamento::Receita->value,
            'categoria_lancamento_id' => null,
            'manual_default_value' => 0,
            'manual_items' => [$this->blankManualItem()],
            'data' => now()->toDateString(),
            'descricao' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankManualItem(): array
    {
        return [
            'nome' => null,
            'valor' => null,
            'categoria_lancamento_id' => null,
            'descricao' => null,
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array<string, mixed>>
     */
    private function manualItemsWithoutCategories(array $items): array
    {
        $items = collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                ...$item,
                'categoria_lancamento_id' => null,
            ])
            ->values()
            ->all();

        return $items === [] ? [$this->blankManualItem()] : $items;
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function registrationItemState(?string $registrationType, array $ids, mixed $amount): array
    {
        if (! is_string($registrationType) || ! array_key_exists($registrationType, RegistrationPaymentAllocator::registrationTypeOptions())) {
            return [];
        }

        /** @var class-string<Model> $registrationType */
        $registrationIds = collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $columns = $registrationType === EquipeTrabalho::class
            ? ['id', 'nome', 'tipo_equipe']
            : ['id', 'nome'];
        $registrations = $registrationType::query()
            ->select($columns)
            ->whereKey($registrationIds->all())
            ->get()
            ->keyBy(fn (Model $registration): int => (int) $registration->getKey());

        return $registrationIds
            ->map(function (int $id) use ($registrations, $amount): ?array {
                $registration = $registrations->get($id);

                if (! $registration) {
                    return null;
                }

                return [
                    'registration_id' => $id,
                    'nome' => (string) ($registration->getAttribute('nome') ?? 'Inscrição #'.$id),
                    'valor' => $this->defaultAmountForRegistration($registration, $amount),
                    'descricao' => null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    private function registrationOptionLabels(?string $registrationType, array $values): array
    {
        return app(RegistrationPaymentAllocator::class)->registrationOptionLabels($registrationType, $values);
    }

    private function defaultRegistrationAmount(): int
    {
        return (int) (app(GeneralSettings::class)->valor_acampamento ?? 0);
    }

    private function registrationAmountNotConfigured(?string $registrationType): bool
    {
        $settings = app(GeneralSettings::class);

        return match ($registrationType) {
            EquipeTrabalho::class => (int) ($settings->valor_equipe_trabalho_interna ?? 0) <= 0
                || (int) ($settings->valor_equipe_trabalho_externa ?? 0) <= 0,
            default => $this->defaultRegistrationAmount() <= 0,
        };
    }

    private function registrationAmountWarningHtml(?string $registrationType): string
    {
        if ($registrationType === EquipeTrabalho::class) {
            return '<div role="alert" class="rounded-lg border border-warning-500/55 bg-warning-500/10 p-4 text-sm text-warning-100">'
                .'<strong class="block text-warning-300">Valores da equipe de trabalho não configurados</strong>'
                .'<span>Configure valores maiores que zero para equipe interna e equipe externa nas configurações antes de criar lançamentos vinculados.</span>'
                .'</div>';
        }

        return '<div role="alert" class="rounded-lg border border-warning-500/55 bg-warning-500/10 p-4 text-sm text-warning-100">'
            .'<strong class="block text-warning-300">Valor do acampamento não configurado</strong>'
            .'<span>O campo de inscrições pode ficar sem opções enquanto o valor estiver zerado nas configurações.</span>'
            .'</div>';
    }

    private function teamConfiguredAmountsHtml(): string
    {
        $settings = app(GeneralSettings::class);
        $internal = MoneyAmount::formatForInput($settings->valor_equipe_trabalho_interna ?? 0);
        $external = MoneyAmount::formatForInput($settings->valor_equipe_trabalho_externa ?? 0);

        return '<div class="grid grid-cols-2 gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">'
            .'<div class="grid gap-1"><span class="text-xs text-gray-500 dark:text-gray-400">Equipe interna</span><strong class="text-sm text-gray-950 dark:text-white">R$ '.e($internal).'</strong></div>'
            .'<div class="grid gap-1"><span class="text-xs text-gray-500 dark:text-gray-400">Equipe externa</span><strong class="text-sm text-gray-950 dark:text-white">R$ '.e($external).'</strong></div>'
            .'</div>';
    }

    private function defaultAmountForRegistration(Model $registration, mixed $fallbackAmount): string
    {
        $amount = $registration instanceof EquipeTrabalho
            ? app(RegistrationPaymentAllocator::class)->expectedAmountFor($registration)
            : MoneyAmount::toCents($fallbackAmount);

        return MoneyAmount::formatForInput($amount ?? 0);
    }

    private function summaryHtml(Get $get): string
    {
        $mode = $get('mode');
        $count = $mode === LancamentoBatchCreator::MODE_MANUAL
            ? collect($get('manual_items') ?? [])->filter(fn (array $item): bool => filled($item['nome'] ?? null))->count()
            : count($get('registration_items') ?? []);

        $nextCode = app(LancamentoBatchCreator::class)->nextBatchCode();
        $label = $mode === LancamentoBatchCreator::MODE_MANUAL ? 'lançamento(s) avulso(s)' : 'lançamento(s) vinculado(s)';

        return '<div class="rounded-lg border border-primary-500/35 bg-primary-500/10 p-4 text-sm text-primary-100">'
            .'<strong class="block text-primary-300">'.e($nextCode).'</strong>'
            .'<span>'.e((string) $count).' '.e($label).' serão criados como pendentes.</span>'
            .'</div>';
    }
}
