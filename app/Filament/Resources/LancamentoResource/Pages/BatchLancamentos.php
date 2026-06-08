<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Enums\TipoLacamento;
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
use Leandrocfe\FilamentPtbrFormFields\Money;

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
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $amount = MoneyAmount::toCents($get('default_value'));
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

                        Html::make(fn (): HtmlString => new HtmlString($this->campAmountWarningHtml()))
                            ->visible(fn (): bool => $this->campAmountNotConfigured())
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
                            ->afterStateUpdated(function (Set $set): void {
                                $set('categoria_lancamento_id', null);
                                $set('manual_items', [$this->blankManualItem()]);
                            })
                            ->columnSpan([
                                'default' => 'full',
                                'lg' => 4,
                            ]),

                        Select::make('categoria_lancamento_id')
                            ->label('Categoria padrão')
                            ->options(fn (Get $get): array => LancamentoForm::categoryOptions($get('tipo')))
                            ->allowHtml()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn (Get $get): bool => $get('mode') === LancamentoBatchCreator::MODE_MANUAL)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $items = collect($get('manual_items') ?? [])
                                    ->map(fn (array $item): array => [
                                        ...$item,
                                        'categoria_lancamento_id' => $get('categoria_lancamento_id'),
                                    ])
                                    ->all();

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
                                $amount = MoneyAmount::toCents($get('manual_default_value'));
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
                                    ->options(fn (Get $get): array => LancamentoForm::categoryOptions($get('../../tipo')))
                                    ->allowHtml()
                                    ->searchable()
                                    ->preload()
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
     * @param  array<int, mixed>  $ids
     * @return array<int, array<string, mixed>>
     */
    private function registrationItemState(?string $registrationType, array $ids, mixed $amount): array
    {
        if (! is_string($registrationType) || ! array_key_exists($registrationType, RegistrationPaymentAllocator::registrationTypeOptions())) {
            return [];
        }

        $amount = MoneyAmount::toCents($amount);

        /** @var class-string<Model> $registrationType */
        return collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->map(function (int $id) use ($registrationType, $amount): ?array {
                $registration = $registrationType::query()->find($id);

                if (! $registration) {
                    return null;
                }

                return [
                    'registration_id' => $id,
                    'nome' => (string) ($registration->getAttribute('nome') ?? 'Inscrição #'.$id),
                    'valor' => $amount,
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
        $options = app(LancamentoBatchCreator::class)->registrationOptions($registrationType);

        return collect($values)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->mapWithKeys(fn (int $id): array => array_key_exists($id, $options) ? [$id => $options[$id]] : [])
            ->all();
    }

    private function defaultRegistrationAmount(): int
    {
        return (int) (app(GeneralSettings::class)->valor_acampamento ?? 0);
    }

    private function campAmountNotConfigured(): bool
    {
        return $this->defaultRegistrationAmount() <= 0;
    }

    private function campAmountWarningHtml(): string
    {
        return '<div role="alert" class="rounded-lg border border-warning-500/55 bg-warning-500/10 p-4 text-sm text-warning-100">'
            .'<strong class="block text-warning-300">Valor do acampamento não configurado</strong>'
            .'<span>O campo de inscrições pode ficar sem opções enquanto o valor estiver zerado nas configurações.</span>'
            .'</div>';
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
