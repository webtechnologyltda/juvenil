<?php

it('adds a rich help slideover to launch create and edit pages', function () {
    $createPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/CreateLancamento.php'));
    $editPage = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/EditLancamento.php'));
    $helpAction = file_get_contents(app_path('Filament/Resources/LancamentoResource/Pages/Concerns/HasLancamentoHelpAction.php'));
    $helpViewPath = resource_path('views/filament/resources/lancamento-resource/partials/help.blade.php');
    $helpHtml = view('filament.resources.lancamento-resource.partials.help')->render();

    expect($createPage)
        ->toContain('HasLancamentoHelpAction')
        ->toContain('$this->lancamentoHelpAction()')
        ->and($editPage)
        ->toContain('HasLancamentoHelpAction')
        ->toContain('$this->lancamentoHelpAction()')
        ->and($helpAction)
        ->toContain("Action::make('lancamentoHelp')")
        ->toContain("->label('Dúvidas')")
        ->toContain('->slideOver()')
        ->toContain('->modalWidth(Width::SevenExtraLarge)')
        ->toContain('->modalSubmitAction(false)')
        ->toContain("view('filament.resources.lancamento-resource.partials.help')")
        ->and($helpViewPath)
        ->toBeFile()
        ->and($helpHtml)
        ->toContain('Como editar um lançamento financeiro')
        ->toContain('<table>')
        ->toContain('lancamento-help-overview.svg')
        ->toContain('lancamento-help-items.svg')
        ->toContain('lancamento-help-comprovantes.svg')
        ->toContain('Passo a passo')
        ->toContain('Exemplos de preenchimento');

    foreach ([
        public_path('img/docs/lancamento-help-overview.svg'),
        public_path('img/docs/lancamento-help-items.svg'),
        public_path('img/docs/lancamento-help-comprovantes.svg'),
    ] as $assetPath) {
        expect($assetPath)->toBeFile();
    }
});
