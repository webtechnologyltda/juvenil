<?php

namespace App\Filament\Resources\LancamentoResource\Forms;

use App\Enums\FormaPagamento;
use App\Enums\StatusLacamento;
use App\Enums\TipoLacamento;
use App\Models\CategoriaLancamento;
use Carbon\Carbon;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
              'lg' => 3,
          ])
              ->schema([
               Section::make('Lançamento')
                   ->description('Controle financeiro do acampamento')
                   ->columns([
                       'sm' => 2,
                   ])
                   ->columnSpan([
                       'default' => 1,
                       'lg' => 2
                   ])
                   ->schema([

                       TextInput::make('nome')
                           ->label('Nome')
                           ->required(),

                       Money::make('valor')
                           ->label('Valor')
                           ->columns(1)
                           ->intFormat()
                           ->prefix(RawJs::make('R$'))
                           ->required(),

                       ToggleButtons::make('tipo')
                           ->label('Tipo de Lançamento')
                           ->options(TipoLacamento::class)
                           ->inline()
                           ->live()
                           ->afterStateUpdated(fn (Set $set): mixed => $set('categoria_lancamento_id', null))
                           ->required(),

                       Select::make('categoria_lancamento_id')
                           ->label('Categoria')
                           ->options(fn (Get $get): array => self::categoryOptions($get('tipo')))
                           ->searchable()
                           ->preload()
                           ->placeholder('Selecione uma categoria')
                           ->helperText('As opções acompanham o tipo do lançamento.')
                           ->disabled(fn (Get $get): bool => blank($get('tipo'))),

                       DatePicker::make('data')
                           ->label('Data de Lançamento')
                           ->required()
                           ->format('Y-m-d')
                           ->displayFormat('d/m/Y')
                           ->default(Carbon::now()->format('Y-m-d')),



                       Select::make('status')
                           ->label('Status')
                           ->options(StatusLacamento::class)
                           ->searchable()
                           ->required(),

                       Select::make('forma_pagamento')
                           ->label('Forma de Pagamento')
                           ->options(FormaPagamento::class)
                           ->searchable()
                           ->required(),

                       TextInput::make('comprador')
                           ->label('Comprador')
                           ->required(),

                       Textarea::make('descricao')
                           ->label('Descricão')
                           ->required(),
                   ]),



                  Section::make( 'Comprovantes' )
                      ->description('Anexar comprovantes do lançamento')
                      ->columnSpan(1)
                      ->schema([
                          Builder::make('comprovante')
                              ->label('Comprovates')
                              ->required()
                              ->afterStateHydrated(static function (Builder $component): void {
                                  $component->rawState(self::normalizeComprovanteState($component->getRawState()));
                                  $component->hydrateItems();
                              })
                              ->blocks([
                                  Block::make(self::COMPROVANTE_BLOCK)
                                      ->label('Comprovante')
                                      ->schema([
                                          TextInput::make('comprovante_nome')
                                              ->label('Nome Comprovante')
                                              ->required(),
                                          FileUpload::make('url')
                                              ->placeholder( 'Tamanho max.: 2MB')
                                              ->hint('Tamanho máximo: 2MB')
                                              ->label('Documento')
                                              ->required()
                                              ->downloadable()
                                              ->openable()
                                              ->multiple()
                                              ->maxSize(2048)
                                              ->acceptedFileTypes(['application/pdf', 'image/*'])
                                              ->previewable(true)
                                              ->columnSpan(2),
                                      ])
                              ])
                      ]),

              ]),
        ];
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
                $data['url'] = self::normalizeFileUploadState($data['url'] ?? []);

                $items[] = [
                    'type' => filled($item['type'] ?? null) ? $item['type'] : self::COMPROVANTE_BLOCK,
                    'data' => $data,
                ];

                continue;
            }

            if (array_key_exists('url', $item) || array_key_exists('comprovante_nome', $item)) {
                $items[] = self::makeComprovanteBlock(
                    name: $item['comprovante_nome'] ?? 'Comprovante',
                    files: self::normalizeFileUploadState($item['url'] ?? []),
                );
            }
        }

        return $items;
    }

    private static function makeComprovanteBlock(string $name = 'Comprovante', array $files = []): array
    {
        return [
            'type' => self::COMPROVANTE_BLOCK,
            'data' => [
                'comprovante_nome' => filled($name) ? $name : 'Comprovante',
                'url' => self::normalizeFileUploadState($files),
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
            ->pluck('nome', 'id')
            ->all();
    }
}
