<?php

namespace App\Filament\Resources\CampistaResource\Pages;

use App\Enums\StatusInscricao;
use App\Filament\Resources\CampistaResource;
use App\Models\Campista;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCampistas extends ListRecords
{
    protected static string $resource = CampistaResource::class;

    /**
     * @var array{total: int, pending: int, paid: int, cancelled: int}|null
     */
    private ?array $registrationStatusCounts = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-s-plus')
                ->label('Criar inscrição'),
        ];
    }

    public function getTabs(): array
    {
        $counts = $this->registrationStatusCounts();

        return [
            'Todas' => Tab::make()
                ->icon('eos-all-inclusive')
                ->badge($counts['total']),
            StatusInscricao::Pendente->name => Tab::make()
                ->icon(StatusInscricao::Pendente->getIcon())
                ->badge($counts['pending'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusInscricao::Pendente)),
            StatusInscricao::Pago->name => Tab::make()
                ->icon(StatusInscricao::Pago->getIcon())
                ->badge($counts['paid'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusInscricao::Pago)),
            StatusInscricao::Cancelado->name => Tab::make()
                ->icon(StatusInscricao::Cancelado->getIcon())
                ->badge($counts['cancelled'])
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', StatusInscricao::Cancelado)),
        ];
    }

    /**
     * @return array{total: int, pending: int, paid: int, cancelled: int}
     */
    private function registrationStatusCounts(): array
    {
        if ($this->registrationStatusCounts !== null) {
            return $this->registrationStatusCounts;
        }

        $counts = Campista::query()
            ->selectRaw(
                'COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS pending,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS paid,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS cancelled',
                [
                    StatusInscricao::Pendente->value,
                    StatusInscricao::Pago->value,
                    StatusInscricao::Cancelado->value,
                ],
            )
            ->firstOrFail();

        return $this->registrationStatusCounts = [
            'total' => (int) $counts->total,
            'pending' => (int) $counts->pending,
            'paid' => (int) $counts->paid,
            'cancelled' => (int) $counts->cancelled,
        ];
    }
}
