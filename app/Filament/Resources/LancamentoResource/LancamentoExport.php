<?php

namespace App\Filament\Resources\LancamentoResource;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Filament\Resources\LancamentoResource\Forms\LancamentoForm;
use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\Lancamento;
use App\Models\LancamentoItem;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class LancamentoExport
{
    public static function getExportColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('Código'),

            ExportColumn::make('nome')
                ->label('Nome do Lançamento'),

            ExportColumn::make('valor')
                ->label('Valor')
                ->formatStateUsing(fn (mixed $state, Lancamento $record): string => self::money(self::signedAmount($record))),

            ExportColumn::make('comprador')
                ->label('Comprador'),

            ExportColumn::make('data')
                ->label('Data de Pagamento')
                ->formatStateUsing(fn (mixed $state): ?string => filled($state) ? Carbon::parse($state)->format('d/m/Y') : null),

            ExportColumn::make('tipo')
                ->label('Tipo de Lançamento')
                ->formatStateUsing(fn (mixed $state): string => self::enumLabel($state, TipoLacamento::class)),

            ExportColumn::make('categories_summary')
                ->label('Categorias'),

            ExportColumn::make('items_summary')
                ->label('Itens')
                ->state(fn (Lancamento $record): string => self::itemsSummary($record)),

            ExportColumn::make('item_values_summary')
                ->label('Valores dos itens')
                ->state(fn (Lancamento $record): string => self::itemValuesSummary($record)),

            ExportColumn::make('item_descriptions_summary')
                ->label('Descrições dos itens')
                ->state(fn (Lancamento $record): string => self::itemDescriptionsSummary($record)),

            ExportColumn::make('registration_payments_summary')
                ->label('Inscrições vinculadas')
                ->state(fn (Lancamento $record): string => self::registrationsSummary($record)),

            ExportColumn::make('batch_code')
                ->label('Lote'),

            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (mixed $state): string => self::enumLabel($state, StatusLacamento::class)),

            ExportColumn::make('forma_pagamento')
                ->label('Forma de Pagamento')
                ->formatStateUsing(fn (mixed $state): string => self::enumLabel($state, FormaPagamento::class)),

            ExportColumn::make('descricao')
                ->label('Descrição')
                ->formatStateUsing(fn (mixed $state): ?string => self::plainText($state)),

            ExportColumn::make('comprovantes_summary')
                ->label('Comprovantes')
                ->state(fn (Lancamento $record): string => self::comprovantesSummary($record)),

            ExportColumn::make('comprovante_observacoes_summary')
                ->label('Observações dos comprovantes')
                ->state(fn (Lancamento $record): string => self::comprovanteObservationsSummary($record)),

            ExportColumn::make('origin')
                ->label('Origem')
                ->formatStateUsing(fn (mixed $state): string => self::originLabel($state)),

            ExportColumn::make('origin_context')
                ->label('Contexto da origem')
                ->formatStateUsing(fn (mixed $state): string => self::originContextLabel($state)),

            ExportColumn::make('created_at')
                ->label('Criado em')
                ->formatStateUsing(fn (mixed $state): ?string => filled($state) ? Carbon::parse($state)->format('d/m/Y H:i') : null),

            ExportColumn::make('updated_at')
                ->label('Atualizado em')
                ->formatStateUsing(fn (mixed $state): ?string => filled($state) ? Carbon::parse($state)->format('d/m/Y H:i') : null),
        ];
    }

    public static function plainText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = html_entity_decode((string) Str::of((string) $value)
            ->replace(['</p>', '</li>', '<br>', '<br/>', '<br />'], "\n")
            ->stripTags(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    public static function itemsSummary(Lancamento $record): string
    {
        return self::items($record)
            ->map(fn (LancamentoItem $item): string => sprintf(
                '%s (%s)',
                self::plainText($item->nome) ?: 'Item sem nome',
                $item->categoria?->nome ?: 'Sem categoria',
            ))
            ->implode('; ');
    }

    public static function itemValuesSummary(Lancamento $record): string
    {
        return self::items($record)
            ->map(fn (LancamentoItem $item): string => sprintf(
                '%s: %s',
                self::plainText($item->nome) ?: 'Item sem nome',
                self::money((int) $item->valor),
            ))
            ->implode('; ');
    }

    public static function itemDescriptionsSummary(Lancamento $record): string
    {
        return self::items($record)
            ->map(fn (LancamentoItem $item): ?string => filled(self::plainText($item->descricao))
                ? sprintf('%s: %s', self::plainText($item->nome) ?: 'Item sem nome', self::plainText($item->descricao))
                : null)
            ->filter()
            ->implode('; ');
    }

    public static function registrationsSummary(Lancamento $record): string
    {
        return self::items($record)
            ->filter(fn (LancamentoItem $item): bool => filled($item->registration_type) && filled($item->registration_id))
            ->map(function (LancamentoItem $item): string {
                $registration = $item->registration;

                return sprintf(
                    '%s #%s - %s (%s)',
                    self::registrationTypeLabel($item->registration_type),
                    $item->registration_id,
                    self::registrationName($registration),
                    self::money((int) $item->valor),
                );
            })
            ->implode('; ');
    }

    public static function comprovantesSummary(Lancamento $record): string
    {
        return collect(LancamentoForm::normalizeComprovanteState(self::comprovanteState($record)))
            ->flatMap(fn (array $block): array => data_get($block, 'data.url', []))
            ->filter(fn (mixed $file): bool => is_string($file) && filled($file))
            ->map(fn (string $file): string => basename(parse_url($file, PHP_URL_PATH) ?: $file))
            ->implode('; ');
    }

    public static function comprovanteObservationsSummary(Lancamento $record): string
    {
        return collect(LancamentoForm::normalizeComprovanteState(self::comprovanteState($record)))
            ->map(fn (array $block): ?string => self::plainText(data_get($block, 'data.observacao')))
            ->filter()
            ->implode('; ');
    }

    private static function enumLabel(mixed $state, string $enumClass): string
    {
        if (blank($state)) {
            return 'Indefinido';
        }

        if ($state instanceof $enumClass) {
            return $state->getLabel() ?? 'Indefinido';
        }

        $enum = $enumClass::tryFrom((int) $state);

        return $enum?->getLabel() ?? 'Indefinido';
    }

    private static function signedAmount(Lancamento $record): int
    {
        $amount = abs((int) $record->valor);

        return $record->tipo === TipoLacamento::Despesa ? -$amount : $amount;
    }

    private static function money(int $amount): string
    {
        $sign = $amount < 0 ? '-' : '';

        return $sign.'R$ '.number_format(abs($amount) / 100, 2, ',', '.');
    }

    /**
     * @return Collection<int, LancamentoItem>
     */
    private static function items(Lancamento $record): Collection
    {
        return $record->relationLoaded('items')
            ? $record->items
            : $record->items()->with(['categoria', 'registration'])->get();
    }

    private static function registrationTypeLabel(?string $registrationType): string
    {
        return match ($registrationType) {
            Campista::class => 'Campista',
            EquipeTrabalho::class => 'Equipe de trabalho',
            default => 'Cadastro',
        };
    }

    private static function registrationName(?Model $registration): string
    {
        return (string) ($registration?->getAttribute('nome') ?? 'Inscrição removida');
    }

    private static function comprovanteState(Lancamento $record): mixed
    {
        if (filled($record->comprovante)) {
            return $record->comprovante;
        }

        $raw = $record->getRawOriginal('comprovante');

        if (! is_string($raw) || blank($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $raw;
    }

    private static function originLabel(mixed $state): string
    {
        return match ($state) {
            Lancamento::ORIGIN_AUTO_REGISTRATION => 'Automático',
            null, '' => 'Manual',
            default => (string) $state,
        };
    }

    private static function originContextLabel(mixed $state): string
    {
        return match ($state) {
            Lancamento::ORIGIN_CONTEXT_OBSERVER => 'Observador da inscrição',
            Lancamento::ORIGIN_CONTEXT_DAILY_RECONCILIATION => 'Regularização diária',
            null, '' => 'Manual',
            default => (string) $state,
        };
    }
}
