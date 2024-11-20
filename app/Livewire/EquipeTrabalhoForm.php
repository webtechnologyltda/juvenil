<?php

namespace App\Livewire;

use App\Models\Campista;
use App\Models\EquipeTrabalho;
use App\Models\User;
use App\Settings\GeneralSettings;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Session;
use Livewire\Component;

class EquipeTrabalhoForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    #[Computed]
    public ?array $settings = [];

    #[Session]
    public $inscrito = false;


    public function render()
    {
        return view('livewire.equipe-trabalho-form');
    }

    public function mount()
    {
        $this->settings = app(GeneralSettings::class)->toArray();
        //pega o valor de comprado do localstorage
        $this->getForm('form')->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema(\App\Filament\Resources\EquipeTrabalhoResource\EquipeTrabalhoForm::getFormCreate());
    }

    public function submitForm(): void
    {
        $this->validate();

        try {

            $this->data = Arr::only($this->data, ['nome', 'avatar_url', 'data_form']);

            $this->data['avatar_url'] = $this->data['avatar_url'][array_key_first($this->data['avatar_url'])]->store('foto-formulario', 'public');

            $voluntario = EquipeTrabalho::create($this->data);

            $this->inscrito = true;
            $this->dispatch('inscricao-realizada');
            Notification::make()
                ->title('Registramos a sua inscrição')
                ->body('Inscrição para equipe de trabalho realizada com sucesso.')
                ->duration(60000)
                ->success()
                ->send();
            $this->reset(['data']);


            $recipient = User::all();

            Notification::make()
                ->info()
                ->title('Nova inscrição')
                ->body(new HtmlString('Uma nova inscrição para equipe de trabalho foi enviada, para o(a) voluntário:
                    <strong>' . strtoupper($voluntario->nome) . '</strong> acesse as inscrições da equipe de trabalho para mais detalhes.'))
                ->sendToDatabase($recipient);

        } catch (\Exception $exception) {
            Notification::make()
                ->title('Ops! Algo deu errado')
                ->body('Por favor, tente novamente mais tarde.')
                ->duration(15000)
                ->danger()
                ->send();
        }
    }

    public function realizarNovaInscricao()

    {
        $this->inscrito = false;
        $this->reset(['data']);
    }


}
