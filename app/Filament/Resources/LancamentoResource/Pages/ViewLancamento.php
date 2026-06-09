<?php

namespace App\Filament\Resources\LancamentoResource\Pages;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\CampistaResource;
use App\Filament\Resources\EquipeTrabalhoResource;
use App\Filament\Resources\LancamentoResource;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
use App\Support\Financeiro\LancamentoReceiptDocuments;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\FontWeight;

class ViewLancamento extends ViewRecord
{
    protected static string $resource = LancamentoResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->getRecord()->loadMissing(['items.categoria', 'items.registration']);
    }

    public function getTitle(): string
    {
        return 'Lançamento #'.$this->getRecord()->getKey();
    }

    public function getSubheading(): ?string
    {
        return 'Resumo financeiro, classificação e vínculos deste lançamento.';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar lançamento')
                ->icon('heroicon-o-pencil-square'),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Resumo financeiro')
                    ->icon('heroicon-o-banknotes')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 6,
                    ])
                    ->schema([
                        TextEntry::make('nome')
                            ->label('Lançamento')
                            ->weight(FontWeight::Bold)
                            ->columnSpan([
                                'default' => 'full',
                                'xl' => 2,
                            ]),

                        TextEntry::make('valor')
                            ->label('Valor total')
                            ->formatStateUsing(fn (int $state, Lancamento $record): string => $this->money($state, $record))
                            ->weight(FontWeight::Bold)
                            ->color(fn (Lancamento $record): string => $this->amountColor($record)),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn (?StatusLacamento $state): string => $state?->getLabel() ?? 'Sem status')
                            ->color(fn (?StatusLacamento $state): string|array|null => $state?->getColor())
                            ->icon(fn (?StatusLacamento $state): ?string => $state?->getIcon()),

                        TextEntry::make('forma_pagamento')
                            ->label('Pagamento')
                            ->badge()
                            ->formatStateUsing(fn (?FormaPagamento $state): string => $state?->getLabel() ?? 'Não informado')
                            ->color(fn (?FormaPagamento $state): string|array|null => $state?->getColor())
                            ->icon(fn (?FormaPagamento $state): ?string => $state?->getIcon()),

                        TextEntry::make('tipo')
                            ->label('Tipo')
                            ->badge()
                            ->formatStateUsing(fn (?TipoLacamento $state): string => $state?->getLabel() ?? 'Sem tipo')
                            ->color(fn (?TipoLacamento $state): string|array|null => $state?->getColor())
                            ->icon(fn (?TipoLacamento $state): ?string => $state?->getIcon()),

                        TextEntry::make('batch_code')
                            ->label('Lote')
                            ->badge()
                            ->placeholder('Sem lote'),
                    ]),

                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])
                    ->schema([
                        Section::make('Dados do lançamento')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ])
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->schema([
                                TextEntry::make('data')
                                    ->label('Data')
                                    ->date('d/m/Y'),

                                TextEntry::make('comprador')
                                    ->label('Comprador')
                                    ->placeholder('Não informado')
                                    ->visible(fn (Lancamento $record): bool => $record->tipo === TipoLacamento::Despesa),

                                TextEntry::make('categories_summary')
                                    ->label('Categorias')
                                    ->state(fn (Lancamento $record): array => $this->categoryNames($record))
                                    ->badge()
                                    ->placeholder('Sem categoria'),

                                TextEntry::make('registration_payments_summary')
                                    ->label('Inscrições vinculadas')
                                    ->state(fn (Lancamento $record): array => $this->registrationSummaries($record))
                                    ->listWithLineBreaks()
                                    ->placeholder('Sem inscrições vinculadas')
                                    ->columnSpanFull(),

                                TextEntry::make('descricao')
                                    ->label('Descrição')
                                    ->html()
                                    ->placeholder('Sem descrição informada')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Comprovantes')
                            ->icon('heroicon-o-paper-clip')
                            ->schema([
                                View::make('filament.resources.lancamento-resource.pages.receipt-documents')
                                    ->viewData(fn (Lancamento $record): array => [
                                        'documents' => app(LancamentoReceiptDocuments::class)->documents($record),
                                    ]),
                            ]),
                    ]),

                Section::make('Itens do lançamento')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Item')->width('22%'),
                                TableColumn::make('Categoria')->width('18%'),
                                TableColumn::make('Valor')->alignment(Alignment::End)->width('14%'),
                                TableColumn::make('Vínculo')->width('24%'),
                                TableColumn::make('Descrição')->wrapHeader(),
                            ])
                            ->schema([
                                TextEntry::make('nome')
                                    ->label('Item')
                                    ->weight(FontWeight::SemiBold),

                                TextEntry::make('categoria.nome')
                                    ->label('Categoria')
                                    ->badge()
                                    ->placeholder('Sem categoria'),

                                TextEntry::make('valor')
                                    ->label('Valor')
                                    ->formatStateUsing(fn (int $state): string => $this->money($state))
                                    ->weight(FontWeight::SemiBold)
                                    ->alignEnd(),

                                TextEntry::make('registration_label')
                                    ->label('Vínculo')
                                    ->state(fn (LancamentoItem $record): string => $this->registrationLabel($record))
                                    ->suffixAction(fn (LancamentoItem $record): ?Action => $this->registrationAction($record))
                                    ->placeholder('Sem vínculo'),

                                TextEntry::make('descricao')
                                    ->label('Descrição')
                                    ->placeholder('Sem descrição informada')
                                    ->wrap(),
                            ])
                            ->placeholder('Nenhum item vinculado ao lançamento.'),
                    ]),
            ]);
    }

    private function amountColor(Lancamento $record): string
    {
        return match ($record->tipo) {
            TipoLacamento::Despesa => 'danger',
            TipoLacamento::Doacao => 'info',
            default => 'success',
        };
    }

    private function money(int $amount, ?Lancamento $record = null): string
    {
        $prefix = $record?->tipo === TipoLacamento::Despesa ? '-R$ ' : 'R$ ';

        return $prefix.number_format(abs($amount) / 100, 2, ',', '.');
    }

    /**
     * @return array<int, string>
     */
    private function categoryNames(Lancamento $record): array
    {
        $record->loadMissing(['items.categoria']);

        return $record->items
            ->map(fn (LancamentoItem $item): ?string => $item->categoria?->nome)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function registrationSummaries(Lancamento $record): array
    {
        $summary = trim($record->registration_payments_summary);

        return $summary === 'Sem inscrições vinculadas'
            ? []
            : explode("\n", $summary);
    }

    private function registrationAction(LancamentoItem $item): ?Action
    {
        $url = $this->registrationUrl($item);

        if ($url === null) {
            return null;
        }

        return Action::make('viewRegistration')
            ->label('Visualizar inscrição')
            ->tooltip('Visualizar inscrição')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->url($url);
    }

    private function registrationUrl(LancamentoItem $item): ?string
    {
        if (blank($item->registration_type) || blank($item->registration_id)) {
            return null;
        }

        $registration = $item->registration;

        if ($registration instanceof Campista) {
            return CampistaResource::getUrl('view', ['record' => $registration]);
        }

        if ($registration instanceof EquipeTrabalho) {
            return EquipeTrabalhoResource::getUrl('view', ['record' => $registration]);
        }

        return null;
    }

    private function registrationLabel(LancamentoItem $item): string
    {
        if (blank($item->registration_type) || blank($item->registration_id)) {
            return 'Sem vínculo';
        }

        $registration = $item->registration;
        $type = class_basename((string) $item->registration_type);
        $name = (string) ($registration?->getAttribute('nome') ?? 'Inscrição removida');

        return sprintf('%s #%s - %s', $type, $item->registration_id, $name);
    }
}
