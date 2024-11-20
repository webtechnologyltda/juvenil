<?php

namespace App\Filament\Reports;

use App\Models\Campista;
use App\Models\Tribo;
use Carbon\Carbon;
use EightyNine\Reports\Components\Header\Layout\HeaderColumn;
use EightyNine\Reports\Components\Header\Layout\HeaderRow;
use EightyNine\Reports\Components\Image;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;

class EnfermagemReport extends Report
{
    public ?string $heading = "Enfermagem";

    public ?string $subHeading = "Relação de campistas inscritos no acampamento que relataram cuidados e uso de medicamentos.";

    public ?string $group = "Campistas";

    public ?string $icon = "healthicons-f-cardiogram-e";

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                HeaderColumn::make()->schema([

                    Image::make(asset('img/logo_simple.png'))->height(50),

                    Text::make('Relação Enfermagem 1º Trekking ')
                        ->title()
                        ->primary(),

                    Text::make('Relação de campistas inscritos no acampamento que relataram cuidados e uso de medicamentos.')
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
                            ->alignCenter()
                            ->label('Tribo'),
                        Body\TextColumn::make('idade')
                            ->alignCenter()
                            ->label('Idade'),
                        Body\TextColumn::make('sexo')
                            ->alignCenter()
                            ->badge()
                            ->icon(fn($state) => $state ? 'ionicon-male-sharp' : 'ionicon-female-sharp')
                            ->color(fn($state) => $state == 'M' ? 'blue' : 'pink')
                            ->label('Sexo'),
                        Body\TextColumn::make('peso')
                            ->alignCenter()
                            ->label('Peso'),
                        Body\TextColumn::make('altura')
                            ->alignCenter()
                            ->label('Altura'),
                        Body\TextColumn::make('imc')
                            ->alignCenter()
                            ->badge()
                            ->color(function ($state) {
                                if ($state == 'N/A') {
                                    return 'gray';
                                }
                                if ($state < 18.5) {
                                    return 'warning';
                                }
                                if ($state >= 18.5 && $state < 24.9) {
                                    return 'success';
                                }
                                if ($state >= 25 && $state < 29.9) {
                                    return 'warning';
                                }
                                if ($state >= 30) {
                                    return 'danger';
                                }
                            })
                            ->label('IMC'),
                        Body\TextColumn::make('tomaRemedio')
                            ->icon(fn($state) => $state ? 'fas-check' : 'fas-x')
                            ->iconColor(fn($state) => $state ? 'success' : 'danger')
                            ->alignCenter()
                            ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não')
                            ->label('Toma Medicação ?'),
                        Body\TextColumn::make('remedio')
                            ->label('Medicação'),
                        Body\TextColumn::make('temRecomendacao')
                            ->icon(fn($state) => $state ? 'fas-check' : 'fas-x')
                            ->iconColor(fn($state) => $state ? 'success' : 'danger')
                            ->alignCenter()
                            ->formatStateUsing(fn($state) => $state ? 'Sim' : 'Não')
                            ->label('Tem Recomendação ?'),
                        Body\TextColumn::make('recomendacao')
                            ->label('Recomendação de Cuidado Informada'),
                    ])
                    ->data($this->getDataQuery())
            ]);
    }

    public function footer(Footer $footer): Footer
    {
        return $footer
            ->schema([
                // ...
            ]);
    }

    public function filterForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('tribo_filter')
                    ->label('Tribo')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(Tribo::pluck('cor', 'id')),

                Toggle::make('presenca_filter')
                    ->default(true)
                    ->label('Presença Confirmada'),
            ]);
    }

    private function getDataQuery()
    {
        return function (?array $filters) {
            if ($filters != []) {
                $data = Campista::query()
                    ->addSelect('campistas.*', 'tribos.cor as cor')
                    ->leftJoin('tribos', 'tribos.id', '=', 'campistas.tribo_id')
                    ->when($filters['tribo_filter'] ?? null, function ($query) use ($filters) {
                        $query->whereIn('tribo_id', $filters['tribo_filter']);
                    })
                    ->when($filters['presenca_filter'] ?? null, function ($query) use ($filters) {
                        if ($filters['presenca_filter'] == true) {
                            $query->where('presenca', true);
                        } else {
                            $query->where('presenca', false);
                        }
                    })
                    ->where(fn($query) => $query
                        ->where('form_data->toma_remedio', '1')
                        ->orWhere('form_data->tem_recomendacao', '1'))
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($data as $d) {
                    $d->tomaRemedio = $d->form_data['toma_remedio'] == '1';
                    $d->temRecomendacao = $d->form_data['tem_recomendacao'] == '1';
                    $d->remedio = array_key_exists('remedio', $d->form_data) ? $d->form_data['remedio'] : '';
                    $d->recomendacao = array_key_exists('recomendacao', $d->form_data) ? $d->form_data['recomendacao'] : '';
                    $d->idade = $d->form_data['data_nacimento'] != '' ? Carbon::createFromFormat('d/m/Y', $d->form_data['data_nacimento'])->age : '';
                    $d->peso = array_key_exists('peso', $d->form_data) ? $d->form_data['peso'] . ' kg' : '-';
                    $d->altura = array_key_exists('altura', $d->form_data) ? $d->form_data['altura'] . ' cm' : '-';
                    $d->sexo = $d->form_data['sexo'];
                    $d->imc = isset($d->form_data['peso']) && isset($d->form_data['altura']) ? number_format($d->form_data['peso'] / (($d->form_data['altura'] / 100) * ($d->form_data['altura'] / 100)), 2, ',', '.') : 'N/A';
                }
            }

            return $data ?? collect([]);
        };
    }
}
