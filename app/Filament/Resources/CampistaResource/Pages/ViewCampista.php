<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Filament\Resources\CampistaResource;
use App\Filament\Resources\CampistaResource\CampistaForm;
use App\Filament\Resources\LancamentoResource;
use App\Models\LancamentoItem;
use App\Support\Campistas\ParishCommunityLabels;
use App\Support\Tribes\TribeColor;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ViewCampista extends ViewRecord
{
    protected static string $resource = CampistaResource::class;

    public function getTitle(): string
    {
        return 'Ficha de inscrição';
    }

    public function getSubheading(): ?string
    {
        return 'Visualização consolidada dos dados enviados pelo campista.';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar inscrição')
                ->icon('heroicon-o-pencil-square')
                ->extraAttributes(['class' => 'juvenil-registration-header-edit'], merge: true),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament.resources.campista-resource.pages.view-campista')
                    ->viewData(fn (): array => [
                        'ficha' => $this->fichaData(),
                    ]),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...CampistaForm::getFormView(),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return CampistaForm::redactSensitiveHealthDetails($data);
    }

    public function fichaData(): array
    {
        $record = $this->getRecord();
        $formData = $record->form_data ?? [];
        $status = $this->statusEnum();
        $payment = $this->paymentEnum();
        $canViewSensitiveHealth = CampistaForm::canViewSensitiveHealth();
        $takesMedicine = $this->truthy(data_get($formData, 'toma_remedio'));
        $hasRecommendation = $this->truthy(data_get($formData, 'tem_recomendacao'));

        return [
            'id' => $record->getKey(),
            'name' => $record->nome,
            'avatar_url' => $this->avatarUrl(),
            'created_at' => $record->created_at?->format('d/m/Y H:i'),
            'status' => [
                'label' => $status?->getLabel() ?? 'Sem status',
                'tone' => $this->statusTone($status),
            ],
            'summary' => [
                [
                    'label' => 'Status',
                    'value' => $status?->getLabel() ?? 'Sem status',
                    'tone' => $this->summaryColor($status?->getColor(), $this->statusTone($status)),
                    'icon' => $status?->getIcon() ?? 'heroicon-o-question-mark-circle',
                    'color' => $this->summaryColor($status?->getColor(), $this->statusTone($status)),
                    'accent' => $this->summaryAccent($status?->getColor(), $this->statusTone($status)),
                ],
                [
                    'label' => 'Pagamento',
                    'value' => $payment?->getLabel() ?? 'Não informado',
                    'tone' => $this->summaryColor($payment?->getColor(), $this->paymentTone($payment)),
                    'icon' => $payment?->getIcon() ?? 'heroicon-o-credit-card',
                    'color' => $this->summaryColor($payment?->getColor(), $this->paymentTone($payment)),
                    'accent' => $this->summaryAccent($payment?->getColor(), $this->paymentTone($payment)),
                ],
                [
                    'label' => 'Presença',
                    'value' => $record->presenca ? 'Confirmada' : 'Pendente',
                    'tone' => $record->presenca ? 'success' : 'warning',
                    'icon' => $record->presenca ? 'heroicon-o-check-circle' : 'heroicon-o-clock',
                    'color' => $record->presenca ? 'success' : 'warning',
                    'accent' => $this->summaryAccent($record->presenca ? 'success' : 'warning'),
                ],
                [
                    'label' => 'Tribo',
                    'value' => $record->tribo?->cor ?? 'Sem tribo',
                    'tone' => $record->tribo ? 'tribe' : 'neutral',
                    'icon' => 'heroicon-o-flag',
                    'color' => $record->tribo ? 'tribe' : 'neutral',
                    'accent' => TribeColor::forTribe($record->tribo),
                ],
            ],
            'sections' => [
                [
                    'title' => 'Dados pessoais',
                    'icon' => 'phosphor-user-list-fill',
                    'fields' => [
                        ['label' => 'Nome completo', 'value' => $record->nome],
                        ['label' => 'Nascimento', 'value' => data_get($formData, 'data_nacimento')],
                        ['label' => 'Sexo', 'value' => $this->sexLabel(data_get($formData, 'sexo'))],
                        ['label' => 'Telefone', 'value' => data_get($formData, 'telefone_campista')],
                        ['label' => 'Rede social', 'value' => data_get($formData, 'rede_social')],
                        ['label' => 'Camiseta', 'value' => $this->shirtLabel($formData)],
                        ['label' => 'Altura', 'value' => $this->withSuffix(data_get($formData, 'altura'), 'cm')],
                        ['label' => 'Peso', 'value' => $this->withSuffix(data_get($formData, 'peso'), 'kg')],
                    ],
                ],
                [
                    'title' => 'Contato e responsável',
                    'icon' => 'ri-parent-fill',
                    'fields' => [
                        ['label' => 'Responsável', 'value' => data_get($formData, 'telefone_reponsavel_nome_1') ?? data_get($formData, 'nome_mae')],
                        ['label' => 'Telefone do responsável', 'value' => data_get($formData, 'telefone_reponsavel_1') ?? data_get($formData, 'telefone_reponsavel')],
                    ],
                ],
                [
                    'title' => 'Endereço',
                    'icon' => 'iconpark-local',
                    'fields' => [
                        ['label' => 'CEP', 'value' => data_get($formData, 'cep')],
                        ['label' => 'Rua', 'value' => data_get($formData, 'rua')],
                        ['label' => 'Número', 'value' => data_get($formData, 'numero')],
                        ['label' => 'Bairro', 'value' => data_get($formData, 'bairro')],
                        ['label' => 'Cidade', 'value' => data_get($formData, 'cidade')],
                        ['label' => 'Estado', 'value' => data_get($formData, 'estado')],
                        ['label' => 'Complemento', 'value' => data_get($formData, 'ponto_referencia'), 'wide' => true],
                    ],
                ],
                [
                    'title' => 'Comunidade e experiência',
                    'icon' => 'fluentui-important-12',
                    'fields' => [
                        ['label' => 'Paróquia', 'value' => $this->parishLabel(data_get($formData, 'paroquia'))],
                        ['label' => 'Comunidade', 'value' => $this->communityLabel(data_get($formData, 'paroquia'), data_get($formData, 'comunidade'))],
                        ['label' => 'Já participou?', 'value' => $this->booleanLabel(data_get($formData, 'ja_participou_retiro'))],
                        ['label' => 'Retiro/acampamento', 'value' => $this->listLabel(data_get($formData, 'retiro_que_participou'))],
                        ['label' => 'Conhece participante?', 'value' => $this->booleanLabel(data_get($formData, 'algum_parente'))],
                        ['label' => 'Nomes indicados', 'value' => $this->listLabel(data_get($formData, 'algum_parente_participante'))],
                        ['label' => 'Declaração', 'value' => $this->declarationLabel(data_get($formData, 'declaro')), 'wide' => true],
                    ],
                ],
                [
                    'title' => 'Saúde e cuidados',
                    'icon' => 'heroicon-o-heart',
                    'fields' => [
                        ['label' => 'Toma remédio?', 'value' => $this->booleanLabel($takesMedicine), 'tone' => $takesMedicine ? 'warning' : 'success'],
                        [
                            'label' => 'Detalhes do remédio',
                            'value' => $this->sensitiveValue(data_get($formData, 'remedio'), $takesMedicine, $canViewSensitiveHealth),
                            'wide' => true,
                        ],
                        ['label' => 'Tem recomendação?', 'value' => $this->booleanLabel($hasRecommendation), 'tone' => $hasRecommendation ? 'warning' : 'success'],
                        [
                            'label' => 'Recomendação de cuidado',
                            'value' => $this->sensitiveValue(data_get($formData, 'recomendacao'), $hasRecommendation, $canViewSensitiveHealth),
                            'wide' => true,
                        ],
                    ],
                ],
                [
                    'title' => 'Controle da inscrição',
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'fields' => [
                        ['label' => 'Data de inscrição', 'value' => $record->created_at?->format('d/m/Y H:i')],
                        ['label' => 'Data de pagamento', 'value' => $record->dia_pagamento?->format('d/m/Y')],
                        ['label' => 'Forma de pagamento', 'value' => $payment?->getLabel()],
                        ['label' => 'Observações', 'value' => $record->observacoes, 'wide' => true],
                    ],
                ],
            ],
            'can_view_payments' => $this->canViewLinkedPayments(),
            'payments' => $this->linkedPaymentsData(),
        ];
    }

    /**
     * @return array<int, array{name: string, amount: string, date: string, method: array{label: string, icon: string, color: string, accent: string}, status: array{label: string, icon: string, color: string, accent: string}, url: ?string}>
     */
    private function linkedPaymentsData(): array
    {
        if (! $this->canViewLinkedPayments()) {
            return [];
        }

        return $this->getRecord()
            ->lancamentoItems()
            ->with('lancamento')
            ->orderByDesc('id')
            ->get()
            ->map(fn (LancamentoItem $payment): array => $this->linkedPaymentData($payment))
            ->values()
            ->all();
    }

    /**
     * @return array{name: string, amount: string, date: string, method: array{label: string, icon: string, color: string, accent: string}, status: array{label: string, icon: string, color: string, accent: string}, url: ?string}
     */
    private function linkedPaymentData(LancamentoItem $payment): array
    {
        $lancamento = $payment->lancamento;
        $method = $lancamento?->forma_pagamento;
        $status = $lancamento?->status;

        return [
            'name' => $lancamento?->nome ?? 'Lançamento removido',
            'amount' => $this->money((int) $payment->valor),
            'date' => $lancamento?->data ? Carbon::parse($lancamento->data)->format('d/m/Y') : 'Sem data',
            'method' => [
                'label' => $method?->getLabel() ?? 'Sem forma',
                'icon' => $method?->getIcon() ?? 'heroicon-o-credit-card',
                'color' => $this->summaryColor($method?->getColor(), 'neutral'),
                'accent' => $this->summaryAccent($method?->getColor(), 'neutral'),
            ],
            'status' => [
                'label' => $status?->getLabel() ?? 'Sem status',
                'icon' => $status?->getIcon() ?? 'heroicon-o-question-mark-circle',
                'color' => $this->summaryColor($status?->getColor(), 'neutral'),
                'accent' => $this->summaryAccent($status?->getColor(), 'neutral'),
            ],
            'url' => $lancamento ? LancamentoResource::getUrl('view', ['record' => $lancamento]) : null,
        ];
    }

    private function canViewLinkedPayments(): bool
    {
        $user = auth()->user();

        return $user !== null
            && $user->can('view_any_lancamento')
            && $user->can('view_lancamento');
    }

    private function money(int $amount): string
    {
        return 'R$ '.number_format(abs($amount) / 100, 2, ',', '.');
    }

    private function statusEnum(): ?StatusInscricao
    {
        $status = $this->getRecord()->status;

        return $status instanceof StatusInscricao ? $status : StatusInscricao::tryFrom((int) $status);
    }

    private function paymentEnum(): ?FormaPagamento
    {
        $payment = $this->getRecord()->forma_pagamento;

        if ($payment instanceof FormaPagamento) {
            return $payment;
        }

        return blank($payment) ? null : FormaPagamento::tryFrom((int) $payment);
    }

    private function avatarUrl(): ?string
    {
        $avatar = $this->getRecord()->avatar_url;

        if (blank($avatar)) {
            return null;
        }

        return Str::startsWith($avatar, ['http://', 'https://', '/'])
            ? $avatar
            : Storage::disk('public')->url($avatar);
    }

    private function statusTone(?StatusInscricao $status): string
    {
        return match ($status) {
            StatusInscricao::Pago => 'success',
            StatusInscricao::Cancelado => 'danger',
            StatusInscricao::Pendente => 'warning',
            default => 'neutral',
        };
    }

    private function paymentTone(?FormaPagamento $payment): string
    {
        return match ($payment) {
            FormaPagamento::Pix => 'info',
            FormaPagamento::Dinheiro => 'success',
            FormaPagamento::Cartao => 'warning',
            FormaPagamento::NaoPago => 'danger',
            default => 'neutral',
        };
    }

    private function summaryColor(string|array|null $color, string $fallback = 'neutral'): string
    {
        if (is_array($color)) {
            $color = collect($color)
                ->filter(fn (mixed $value): bool => is_string($value) && filled($value))
                ->first();
        }

        return is_string($color) && filled($color) ? $color : $fallback;
    }

    private function summaryAccent(string|array|null $color, string $fallback = 'neutral'): string
    {
        return match ($this->summaryColor($color, $fallback)) {
            'success' => '#22c55e',
            'warning' => '#facc15',
            'danger' => '#fb7185',
            'info' => '#9ddbef',
            'teal' => '#2dd4bf',
            'orange' => '#f46b12',
            'violet' => '#a78bfa',
            default => '#94a3b8',
        };
    }

    private function sexLabel(mixed $sex): ?string
    {
        return match ($sex) {
            'M' => 'Masculino',
            'F' => 'Feminino',
            default => filled($sex) ? (string) $sex : null,
        };
    }

    private function shirtLabel(array $formData): ?string
    {
        $size = data_get($formData, 'tamanho_camiseta');

        if ($size === 'O') {
            return filled(data_get($formData, 'tamanho_camiseta_outro'))
                ? 'Outro: '.data_get($formData, 'tamanho_camiseta_outro')
                : 'Outro';
        }

        return $size;
    }

    private function withSuffix(mixed $value, string $suffix): ?string
    {
        return filled($value) ? trim((string) $value).' '.$suffix : null;
    }

    private function parishLabel(mixed $parish): ?string
    {
        return ParishCommunityLabels::parishLabel($parish);
    }

    private function communityLabel(mixed $parish, mixed $community): ?string
    {
        return ParishCommunityLabels::communityLabel($parish, $community);
    }

    private function booleanLabel(mixed $value): string
    {
        return $this->truthy($value) ? 'Sim' : 'Não';
    }

    private function declarationLabel(mixed $value): string
    {
        return $this->truthy($value)
            ? 'Declara nunca ter participado'
            : 'Já participou de alguma edição';
    }

    private function listLabel(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return is_array($value) ? implode(', ', array_filter($value)) : (string) $value;
    }

    private function sensitiveValue(mixed $value, bool $hasSensitiveInfo, bool $canViewSensitiveHealth): ?string
    {
        if (! $hasSensitiveInfo) {
            return null;
        }

        if (! $canViewSensitiveHealth) {
            return 'Informação restrita';
        }

        return filled($value) ? (string) $value : 'Não detalhado';
    }

    private function truthy(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'sim', 'yes', 'on'], true);
    }
}
