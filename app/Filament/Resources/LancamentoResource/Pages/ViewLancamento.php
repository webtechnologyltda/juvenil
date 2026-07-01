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
use App\Support\Financeiro\LancamentoRegistrationCard;
use App\Support\IconBadge;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\HtmlString;

class ViewLancamento extends ViewRecord
{
    protected static string $resource = LancamentoResource::class;

    public function mount(int|string $record): void
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
                                TextEntry::make('nome')
                                    ->label('Nome')
                                    ->weight(FontWeight::Bold)
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 4,
                                    ]),

                                TextEntry::make('data')
                                    ->label('Data de Lançamento')
                                    ->date('d/m/Y')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 2,
                                    ]),

                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn (?StatusLacamento $state): string => $state?->getLabel() ?? 'Sem status')
                                    ->color(fn (?StatusLacamento $state): string|array|null => $state?->getColor())
                                    ->icon(fn (?StatusLacamento $state): ?string => $state?->getIcon())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 2,
                                    ]),

                                TextEntry::make('forma_pagamento')
                                    ->label('Forma de Pagamento')
                                    ->badge()
                                    ->formatStateUsing(fn (?FormaPagamento $state): string => $state?->getLabel() ?? 'Não informado')
                                    ->color(fn (?FormaPagamento $state): string|array|null => $state?->getColor())
                                    ->icon(fn (?FormaPagamento $state): ?string => $state?->getIcon())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 2,
                                    ]),

                                TextEntry::make('batch_code')
                                    ->label('Lote')
                                    ->badge()
                                    ->placeholder('Sem lote')
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 2,
                                    ]),

                                TextEntry::make('tipo')
                                    ->label('Tipo de Lançamento')
                                    ->badge()
                                    ->formatStateUsing(fn (?TipoLacamento $state): string => $state?->getLabel() ?? 'Sem tipo')
                                    ->color(fn (?TipoLacamento $state): string|array|null => $state?->getColor())
                                    ->icon(fn (?TipoLacamento $state): ?string => $state?->getIcon())
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 2,
                                        'xl' => 4,
                                    ]),

                                TextEntry::make('comprador')
                                    ->label('Comprador')
                                    ->placeholder('Não informado')
                                    ->visible(fn (Lancamento $record): bool => $record->tipo === TipoLacamento::Despesa)
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 4,
                                    ]),

                                Html::make(fn (Lancamento $record): HtmlString => $this->totalPreviewHtml($record))
                                    ->columnSpan([
                                        'default' => 'full',
                                        'md' => 1,
                                        'xl' => 4,
                                    ])
                                    ->columnStart([
                                        'md' => 2,
                                        'xl' => 9,
                                    ]),

                                TextEntry::make('descricao')
                                    ->label('Descrição')
                                    ->html()
                                    ->placeholder('Sem descrição informada')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Itens do lançamento')
                            ->description('Classifique valores, categorias e vínculos financeiros.')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 8,
                            ])
                            ->schema([
                                RepeatableEntry::make('items')
                                    ->hiddenLabel()
                                    ->contained()
                                    ->grid(1)
                                    ->columns([
                                        'default' => 1,
                                        'md' => 2,
                                        'xl' => 12,
                                    ])
                                    ->schema([
                                        TextEntry::make('nome')
                                            ->label('Nome')
                                            ->weight(FontWeight::SemiBold)
                                            ->columnSpan([
                                                'default' => 'full',
                                                'md' => 1,
                                                'xl' => 4,
                                            ]),

                                        TextEntry::make('valor')
                                            ->label('Valor')
                                            ->formatStateUsing(fn (int $state): string => $this->money($state))
                                            ->weight(FontWeight::SemiBold)
                                            ->columnSpan([
                                                'default' => 'full',
                                                'md' => 1,
                                                'xl' => 4,
                                            ]),

                                        TextEntry::make('categoria_label')
                                            ->label('Categoria')
                                            ->state(fn (LancamentoItem $record): HtmlString => $this->categoryLabel($record))
                                            ->html()
                                            ->placeholder('Sem categoria')
                                            ->columnSpan([
                                                'default' => 'full',
                                                'md' => 1,
                                                'xl' => 4,
                                            ]),

                                        TextEntry::make('registration_type')
                                            ->label('Tipo da inscrição')
                                            ->state(fn (LancamentoItem $record): ?string => $this->registrationTypeLabel($record))
                                            ->placeholder('Sem vínculo')
                                            ->visible(fn (): bool => $this->getRecord()->tipo !== TipoLacamento::Despesa)
                                            ->columnSpan([
                                                'default' => 'full',
                                                'md' => 1,
                                                'xl' => 3,
                                            ]),

                                        TextEntry::make('registration_label')
                                            ->label('Inscrição')
                                            ->state(fn (LancamentoItem $record): string|HtmlString|null => $this->registrationLabel($record))
                                            ->html()
                                            ->suffixAction(fn (LancamentoItem $record): ?Action => $this->registrationAction($record))
                                            ->placeholder('Sem vínculo')
                                            ->visible(fn (): bool => $this->getRecord()->tipo !== TipoLacamento::Despesa)
                                            ->columnSpan([
                                                'default' => 'full',
                                                'md' => 2,
                                                'xl' => 9,
                                            ]),

                                        TextEntry::make('descricao')
                                            ->label('Descrição')
                                            ->placeholder('Sem descrição informada')
                                            ->columnSpanFull(),
                                    ])
                                    ->placeholder('Nenhum item vinculado ao lançamento.'),
                            ]),

                        Section::make('Comprovantes')
                            ->description('Recibos, PIX e demais documentos do lançamento.')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 4,
                            ])
                            ->schema([
                                View::make('filament.resources.lancamento-resource.pages.receipt-documents')
                                    ->viewData(fn (Lancamento $record): array => [
                                        'documents' => app(LancamentoReceiptDocuments::class)->documents($record),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private function money(int $amount, ?Lancamento $record = null): string
    {
        $prefix = $record?->tipo === TipoLacamento::Despesa ? '-R$ ' : 'R$ ';

        return $prefix.number_format(abs($amount) / 100, 2, ',', '.');
    }

    private function totalPreviewHtml(Lancamento $record): HtmlString
    {
        return new HtmlString(
            '<div role="status" class="flex w-fit min-w-72 flex-col items-start gap-1 rounded-lg bg-white/5 px-5 py-4">'
                .'<span class="text-sm font-semibold leading-none text-white">Total do lançamento</span>'
                .'<span class="text-[2.75rem] font-black leading-none tracking-normal" style="color: '.e($this->amountTextColor($record)).'">'.e($this->money((int) $record->valor, $record)).'</span>'
            .'</div>',
        );
    }

    private function amountTextColor(Lancamento $record): string
    {
        return match ($record->tipo) {
            TipoLacamento::Despesa => '#f87171',
            TipoLacamento::Doacao => '#60a5fa',
            default => '#4ade80',
        };
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

    private function categoryLabel(LancamentoItem $item): HtmlString
    {
        if (! $item->categoria) {
            return new HtmlString('<span class="text-sm text-gray-400">Sem categoria</span>');
        }

        return IconBadge::tile($item->categoria, $item->categoria->nome, fallbackIcon: 'heroicon-o-tag');
    }

    private function registrationLabel(LancamentoItem $item): string|HtmlString
    {
        if (blank($item->registration_type) || blank($item->registration_id)) {
            return '';
        }

        $registration = $item->registration;

        if ($registration instanceof EquipeTrabalho) {
            return LancamentoRegistrationCard::forTeam($registration);
        }

        $type = class_basename((string) $item->registration_type);
        $name = (string) ($registration?->getAttribute('nome') ?? 'Inscrição removida');

        return sprintf('%s #%s - %s', $type, $item->registration_id, $name);
    }

    private function registrationTypeLabel(LancamentoItem $item): ?string
    {
        if (blank($item->registration_type) || blank($item->registration_id)) {
            return null;
        }

        return $item->registration_type === EquipeTrabalho::class
            ? 'Equipe de trabalho'
            : 'Campista';
    }
}
