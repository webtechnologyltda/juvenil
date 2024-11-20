<?php

namespace App\Filament\Reports;

use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\Tribo;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use EightyNine\Reports\Components\Header\Layout\HeaderColumn;
use EightyNine\Reports\Components\Header\Layout\HeaderRow;
use EightyNine\Reports\Components\Image;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Report;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;

class CampistasReport extends Report
{
    public ?string $heading = "Relação de Campistas";

    public ?string $subHeading = "Relatório baseado na relação de campistas inscritos no acampamento";

    public ?string $group = "Campistas";

    public ?string $icon = "rpg-campfire";


    public function header(Header $header): Header
    {
        return $header
            ->schema([
                HeaderColumn::make()->schema([

                    Image::make(asset('img/logo_simple.png'))->height(50),

                    Text::make('1ºAcampamento Trekking ')
                        ->title()
                        ->primary(),

                    Text::make('Relação de campistas inscritos no acampamento.')
                        ->secondary()
                        ->subTitle(),

                    HeaderRow::make()->schema([
                        Text::make('Relatório gerado em ' . Carbon::now()->format('d/m/Y H:i:s'))
                    ])->alignRight()

                ])

            ]);
    }


    public function body(Body $body): Body
    {
        return $body
            ->schema([
                Body\Table::make()
                    ->columns([
                        Body\TextColumn::make('id')
                            ->label('#'),

                        Body\TextColumn::make('nome')
                            ->label('Nome'),

                        Body\TextColumn::make('cor')
                            ->label('Tribo'),

                        Body\TextColumn::make('nomeResponsavel')
                            ->label('Nome do Responsável'),

                        Body\TextColumn::make('telefoneResponsavel')
                            ->label('Tel. do Responsável'),

                        Body\TextColumn::make('enderecoCompleto')
                            ->label('Endereço'),

                    ])
                    ->data($this->getDataQuery())
            ]);
    }

    public function footer(Footer $footer): Footer
    {
        return $footer
            ->schema([
            ]);
    }

    public function filterForm(Form $form): Form
    {
        $data = Campista::all();
        $optionsCidadeEstado = fluent([]);
        foreach ($data as $d) {
            $optionsCidadeEstado[$d['form_data']['cidade'] . ' - ' . $d['form_data']['estado']] = $d['form_data']['cidade'] . ' - ' . $d['form_data']['estado'];
        }

        return $form
            ->schema([


                Select::make('cidade_estado_filter')
                    ->label('Cidade')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated( function (Set $set) {
                        $set('bairro_filter', null);
                    })
                    ->options($optionsCidadeEstado),

                CheckboxList::make('bairro_filter')
                    ->label('Bairro')
                    ->hiddenLabel()
                    ->disabled(fn (Get $get): bool => !filled($get('cidade_estado_filter')) )
                    ->options(fn (Get $get): array => Campista::query()
                    ->where('form_data->cidade', explode(' - ', $get('cidade_estado_filter'))[array_key_first(explode(' - ', $get('cidade_estado_filter')))])
                    ->select('form_data->bairro as bairro')
                    ->pluck('bairro', 'bairro')->toArray())
                    ->bulkToggleable(),

                Select::make('tribo_filter')
                    ->label('Tribo')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(Tribo::pluck('cor', 'id')),

                Toggle::make('presenca_filter')
                    ->default(true)
                    ->label('Presença Confirmada'),

                Select::make('status_filter')
                    ->label('Status')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(StatusInscricao::class),
            ]);
    }

    private function getDataQuery(): \Closure
    {
        return function (?array $filters) {
            if ($filters != []) {
                $data = Campista::query()
                    ->leftJoin('tribos', 'tribos.id', '=', 'campistas.tribo_id')
                    ->addSelect('campistas.*', 'tribos.cor as cor')
                    ->when(array_key_exists('tribo_filter', $filters) && $filters['tribo_filter'], function ($query) use ($filters) {
                        $query->whereIn('tribo_id', $filters['tribo_filter']);
                    })
                    ->when(array_key_exists('bairro_filter', $filters)
                        && array_key_exists('cidade_estado_filter', $filters)
                        && $filters['bairro_filter'] && $filters['bairro_filter'] != []
                        && $filters['cidade_estado_filter'] && $filters['cidade_estado_filter'] != [],
                        function ($query) use ($filters) {
                            $cidade = explode(' - ', $filters['cidade_estado_filter']);
                            $query->whereIn('form_data->bairro', $filters['bairro_filter'])->where('form_data->cidade', $cidade[0]);
                    })
                    ->when(array_key_exists('cidade_filter', $filters) && $filters['cidade_estado_filter'], function ($query) use ($filters) {
                        $temp = explode(' - ', $filters['cidade_estado_filter']);

                        $query->where('form_data->cidade', $temp[0])
                            ->where('form_data->estado', $temp[1]);
                    })
                    ->when(array_key_exists('status_filter', $filters) && $filters['status_filter'], function ($query) use ($filters) {
                        $query->whereIn('status', $filters['status_filter']);
                    })
                    ->when(array_key_exists('presenca_filter', $filters) && $filters['presenca_filter'], function ($query) use ($filters) {
                        if ($filters['presenca_filter'] == true) {
                            $query->where('presenca', true);
                        } else {
                            $query->where('presenca', false);
                        }
                    })
                    ->get();

                foreach ($data as $d) {
                    $d->bairro = $d->form_data['bairro'];
                    $d->cidade = $d->form_data['cidade'];
                    $d->rua = $d->form_data['rua'];
                    $d->estado = $d->form_data['estado'];
                    $d->numero = $d->form_data['numero'];
                    $d->enderecoCompleto = $d->form_data['rua'] . ', ' . $d->form_data['numero'] . ' - ' . $d->form_data['bairro'] . ' - ' . $d->form_data['cidade'] . ' - ' . $d->form_data['estado'];
                    $d->telefoneResponsavel = $d->form_data['telefone_reponsavel_1'] ?? '';
                    $d->nomeResponsavel = $d->form_data['telefone_reponsavel_nome_1'] ?? '';
                }
            }

            return $data ?? collect([]);
        };
    }
}
