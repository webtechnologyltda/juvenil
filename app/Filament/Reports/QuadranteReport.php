<?php

namespace App\Filament\Reports;

use Carbon\Carbon;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use EightyNine\Reports\Components\Header\Layout\HeaderColumn;
use EightyNine\Reports\Components\Header\Layout\HeaderRow;
use EightyNine\Reports\Components\Image;
use EightyNine\Reports\Components\Text;
use EightyNine\Reports\Report;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use App\Enums\FormaPagamento;
use App\Enums\StatusInscricao;
use App\Models\Campista;
use App\Models\Tribo;
class QuadranteReport extends Report
{
    public ?string $heading = "Quadrante ";

    public ?string $group = "Campistas";

    // public ?string $subHeading = "A great report";

    public function header(Header $header): Header
    {

        return $header
            ->schema([
                HeaderColumn::make()->schema([

                    Image::make(asset('img/logo_simple.png'))->height(50),

                    Text::make('Campistas 1º Trekking')
                        ->title()
                        ->primary(),

                    Text::make('Quadrante de campistas inscritos no acampamento.')
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

                        Body\TextColumn::make('amigoParticipante')
                            ->label('Parente/amigo no Trekking'),
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
                    ->when($filters['status_filter'] ?? null, function ($query) use ($filters) {
                        $query->whereIn('status', $filters['status_filter']);
                    })
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($data as $d) {

                    $d->tomaRemedio = array_key_exists('toma_remedio', $d->form_data) ? $d->form_data['toma_remedio'] == '1' : false;
                    $d->temRecomendacao = array_key_exists('tem_recomendacao', $d->form_data) ? $d->form_data['tem_recomendacao'] == '1' : false;
                    $d->remedio = array_key_exists('remedio', $d->form_data) ? $d->form_data['remedio'] : '';
                    $d->recomendacao = array_key_exists('recomendacao', $d->form_data) ? $d->form_data['recomendacao'] : '';
                    $d->idade = $d->form_data['data_nacimento'] != '' ? Carbon::createFromFormat('d/m/Y', $d->form_data['data_nacimento'])->age : '';
                    $d->peso = $d->form_data['peso'] ? $d->form_data['peso'] . ' kg' : '-';
                    $d->altura = $d->form_data['altura'] ? $d->form_data['altura'] . ' cm' : '-';
                    $d->sexo = $d->form_data['sexo'];
                    $d->amigoParticipante = $d->form_data['algum_parente_participante'] ?? ' ';
                    $d->imc = isset($d->form_data['peso']) && isset($d->form_data['altura']) ? number_format($d->form_data['peso'] / (($d->form_data['altura'] / 100) * ($d->form_data['altura'] / 100)), 2, ',', '.') : 'N/A';
                }
            }

            return $data ?? collect([]);
        };
    }
}
