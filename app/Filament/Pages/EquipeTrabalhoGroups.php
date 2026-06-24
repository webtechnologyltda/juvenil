<?php

namespace App\Filament\Pages;

use App\Enums\TipoEquipeTrabalho;
use App\Models\EquipeTrabalho;
use App\Settings\GeneralSettings;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class EquipeTrabalhoGroups extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $slug = 'equipe-trabalho-grupos';

    protected static ?string $title = 'Grupos de Equipe de Trabalho';

    protected static ?string $navigationLabel = 'Grupos de Equipe';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Gestão Acampamento';

    protected static ?int $navigationSort = 35;

    protected string $view = 'filament.pages.equipe-trabalho-groups';

    public function getSubheading(): ?string
    {
        return 'Gerencie os nomes dos grupos e a regra de valor aplicada às inscrições da equipe de trabalho.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, ?string $sortColumn, ?string $sortDirection): array => $this->groupRecords(
                search: $search,
                sortColumn: $sortColumn,
                sortDirection: $sortDirection,
            ))
            ->columns([
                TextColumn::make('nome')
                    ->label('Grupo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('members_count')
                    ->label('Inscritos')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('tipo_equipe_label')
                    ->label('Regra de valor')
                    ->badge()
                    ->color(fn (array $record): string => $record['tipo_equipe_color']),

                TextColumn::make('valor_referencia')
                    ->label('Valor usado nos lançamentos')
                    ->alignEnd()
                    ->state(fn (array $record): string => $record['valor_referencia_label']),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Editar grupo')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalHeading(fn (array $record): string => 'Editar grupo '.$record['nome'])
                    ->modalDescription('A alteração será aplicada a todas as inscrições vinculadas a este grupo.')
                    ->schema([
                        Hidden::make('nome_original'),

                        TextInput::make('nome')
                            ->label('Nome do grupo')
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $currentName = trim((string) $get('nome_original'));
                                    $newName = trim((string) $value);

                                    if ($newName !== '' && $newName !== $currentName && $this->groupNameExists($newName)) {
                                        $fail('Já existe um grupo de equipe de trabalho com esse nome.');
                                    }
                                },
                            ]),

                        ToggleButtons::make('tipo_equipe')
                            ->label('Regra de valor')
                            ->options(TipoEquipeTrabalho::class)
                            ->inline()
                            ->required()
                            ->helperText('Esta regra define se o grupo usa o valor configurado para equipe interna ou externa.'),
                    ])
                    ->fillForm(fn (array $record): array => [
                        'nome_original' => $record['nome'],
                        'nome' => $record['nome'],
                        'tipo_equipe' => $record['tipo_equipe'] ?? TipoEquipeTrabalho::Interna->value,
                    ])
                    ->action(fn (array $data, array $record): mixed => $this->updateGroup($record, $data)),
            ])
            ->emptyStateHeading('Nenhum grupo cadastrado')
            ->emptyStateDescription('Os grupos aparecem aqui quando houver inscrições de equipe de trabalho com o campo Equipe preenchido.')
            ->emptyStateIcon(Heroicon::OutlinedRectangleGroup)
            ->paginated([10, 25, 50])
            ->defaultSort('nome');
    }

    /**
     * @return array<int, array{
     *     __key: string,
     *     nome: string,
     *     members_count: int,
     *     tipo_equipe: int|null,
     *     tipo_equipe_label: string,
     *     tipo_equipe_color: string,
     *     valor_referencia_label: string
     * }>
     */
    private function groupRecords(?string $search, ?string $sortColumn, ?string $sortDirection): array
    {
        $groups = DB::table((new EquipeTrabalho)->getTable())
            ->select('descricao')
            ->selectRaw('COUNT(*) as members_count')
            ->selectRaw('COUNT(DISTINCT tipo_equipe) as distinct_types')
            ->selectRaw('MIN(tipo_equipe) as tipo_equipe')
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->when(filled($search), fn ($query) => $query->where('descricao', 'like', '%'.self::escapeLike((string) $search).'%'))
            ->groupBy('descricao')
            ->get()
            ->map(fn (object $group): array => $this->formatGroupRecord($group));

        return $this->sortGroupRecords($groups, $sortColumn, $sortDirection)
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     __key: string,
     *     nome: string,
     *     members_count: int,
     *     tipo_equipe: int|null,
     *     tipo_equipe_label: string,
     *     tipo_equipe_color: string,
     *     valor_referencia_label: string
     * }
     */
    private function formatGroupRecord(object $group): array
    {
        $name = (string) $group->descricao;
        $type = ((int) $group->distinct_types) === 1
            ? $this->normalizeTeamType($group->tipo_equipe)
            : null;

        return [
            '__key' => sha1($name),
            'nome' => $name,
            'members_count' => (int) $group->members_count,
            'tipo_equipe' => $type?->value,
            'tipo_equipe_label' => $type?->getLabel() ?? 'Misto',
            'tipo_equipe_color' => is_string($type?->getColor()) ? $type->getColor() : 'warning',
            'valor_referencia_label' => $this->referenceAmountLabel($type),
        ];
    }

    private function normalizeTeamType(mixed $type): ?TipoEquipeTrabalho
    {
        if ($type instanceof TipoEquipeTrabalho) {
            return $type;
        }

        if ($type === null || $type === '') {
            return null;
        }

        return TipoEquipeTrabalho::tryFrom((int) $type);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $groups
     * @return Collection<int, array<string, mixed>>
     */
    private function sortGroupRecords(Collection $groups, ?string $sortColumn, ?string $sortDirection): Collection
    {
        $descending = $sortDirection === 'desc';

        return match ($sortColumn) {
            'members_count' => $groups->sortBy('members_count', SORT_REGULAR, $descending),
            'tipo_equipe_label' => $groups->sortBy('tipo_equipe_label', SORT_NATURAL | SORT_FLAG_CASE, $descending),
            'valor_referencia' => $groups->sortBy('valor_referencia_label', SORT_NATURAL | SORT_FLAG_CASE, $descending),
            default => $groups->sortBy('nome', SORT_NATURAL | SORT_FLAG_CASE, $descending),
        };
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $data
     */
    private function updateGroup(array $record, array $data): void
    {
        $currentName = trim((string) ($record['nome'] ?? ''));
        $newName = trim((string) ($data['nome'] ?? ''));
        $type = $this->normalizeTeamType($data['tipo_equipe'] ?? null);

        if ($currentName === '' || $newName === '' || $type === null) {
            throw ValidationException::withMessages([
                'nome' => 'Informe nome e regra de valor válidos para o grupo.',
            ]);
        }

        if ($newName !== $currentName && $this->groupNameExists($newName)) {
            throw ValidationException::withMessages([
                'nome' => 'Já existe um grupo de equipe de trabalho com esse nome.',
            ]);
        }

        $updated = EquipeTrabalho::query()
            ->where('descricao', $currentName)
            ->update([
                'descricao' => $newName,
                'tipo_equipe' => $type->value,
            ]);

        $this->flushCachedTableRecords();

        $this->getTable()->getRecords();

        Notification::make()
            ->title('Grupo atualizado')
            ->body(sprintf('%d inscrição(ões) de equipe de trabalho atualizada(s).', $updated))
            ->success()
            ->send();
    }

    private function groupNameExists(string $name): bool
    {
        return EquipeTrabalho::query()
            ->where('descricao', $name)
            ->exists();
    }

    private function referenceAmountLabel(?TipoEquipeTrabalho $type): string
    {
        if (! $type) {
            return 'Defina uma regra';
        }

        $field = $type->configuredAmountField();
        $amount = (int) (app(GeneralSettings::class)->{$field} ?? 0);

        return 'R$ '.number_format($amount / 100, 2, ',', '.');
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
