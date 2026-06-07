<?php

use App\Livewire\CampistaForm;
use App\Livewire\EquipeTrabalhoForm;
use Filament\Actions\Contracts\HasActions;

it('uses the Jefferson Goncalves CEP field in address forms', function () {
    $forms = [
        file_get_contents(app_path('Livewire/CampistaForm.php')),
        file_get_contents(app_path('Filament/Resources/CampistaResource/CampistaForm.php')),
        file_get_contents(app_path('Filament/Resources/EquipeTrabalhoResource/EquipeTrabalhoForm.php')),
    ];

    foreach ($forms as $form) {
        expect($form)
            ->toContain('use JeffersonGoncalves\Filament\CepField\Forms\Components\CepInput;')
            ->toContain('CepInput::make')
            ->toContain('->setStreetField(')
            ->toContain('->setNeighborhoodField(')
            ->toContain('->setCityField(')
            ->toContain('->setStateField(')
            ->not->toContain('use Leandrocfe\FilamentPtbrFormFields\Cep;')
            ->not->toContain('Cep::make');
    }
});

it('supports Filament form actions on public registration Livewire components', function () {
    expect(class_implements(CampistaForm::class))
        ->toContain(HasActions::class)
        ->and(method_exists(CampistaForm::class, 'mountAction'))->toBeTrue()
        ->and(class_implements(EquipeTrabalhoForm::class))->toContain(HasActions::class)
        ->and(method_exists(EquipeTrabalhoForm::class, 'mountAction'))->toBeTrue();

    foreach ([
        file_get_contents(resource_path('views/livewire/campista-form.blade.php')),
        file_get_contents(resource_path('views/livewire/equipe-trabalho-form.blade.php')),
    ] as $view) {
        expect($view)->toContain('<x-filament-actions::modals />');
    }
});
