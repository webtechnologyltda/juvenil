<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Filament\Resources\CampistaResource;
use App\Filament\Resources\CampistaResource\CampistaForm;
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
                    'tone' => $this->statusTone($status),
                ],
                [
                    'label' => 'Pagamento',
                    'value' => $payment?->getLabel() ?? 'Não informado',
                    'tone' => $this->paymentTone($payment),
                ],
                [
                    'label' => 'Presença',
                    'value' => $record->presenca ? 'Confirmada' : 'Pendente',
                    'tone' => $record->presenca ? 'success' : 'warning',
                ],
                [
                    'label' => 'Tribo',
                    'value' => $record->tribo?->cor ?? 'Sem tribo',
                    'tone' => $record->tribo ? 'info' : 'neutral',
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
                        ['label' => 'Comprovante', 'value' => data_get($formData, 'comprovante_nome')],
                        ['label' => 'Observações', 'value' => $record->observacoes, 'wide' => true],
                    ],
                ],
            ],
            'documents' => $this->documentLinks(data_get($formData, 'comprovante')),
        ];
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

    private function documentLinks(mixed $documents): array
    {
        if (blank($documents)) {
            return [];
        }

        $documents = is_array($documents) ? $documents : [$documents];

        return collect($documents)
            ->filter()
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'url' => Str::startsWith($path, ['http://', 'https://', '/']) ? $path : Storage::url($path),
            ])
            ->values()
            ->all();
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
                ? 'Outro: ' . data_get($formData, 'tamanho_camiseta_outro')
                : 'Outro';
        }

        return $size;
    }

    private function withSuffix(mixed $value, string $suffix): ?string
    {
        return filled($value) ? trim((string) $value) . ' ' . $suffix : null;
    }

    private function parishLabel(mixed $parish): ?string
    {
        return match ((string) $parish) {
            '0' => 'Paróquia São Domingos e Nossa Senhora do Carmo',
            '1' => 'Paróquia Santa Luzia',
            '2' => 'Outra paróquia',
            default => filled($parish) ? (string) $parish : null,
        };
    }

    private function communityLabel(mixed $parish, mixed $community): ?string
    {
        if (blank($community)) {
            return null;
        }

        $communities = match ((string) $parish) {
            '0' => [
                'Comunidade Matriz de São Domingos e Nossa Senhora do Carmo',
                'Comunidade Nossa Senhora das Graças',
                'Comunidade São Paulo',
                'Comunidade Nossa Senhora do Rosário',
                'Comunidade Imaculado Coração de Maria',
            ],
            '1' => [
                'Comunidade Santa Luzia - Machados',
                'Comunidade Santa Teresinha',
                'Comunidade São Francisco',
                'Comunidade Sagrado Coração',
                'Comunidade Nossa Senhora de Fátima',
                'Comunidade Santo Agostinho',
                'Comunidade São José',
                'Comunidade Nossa Senhora Aparecida',
            ],
            default => [],
        };

        return $communities[(int) $community] ?? (string) $community;
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
