<?php

it('translates the Filament select no options message to pt_BR', function () {
    app()->setLocale('pt_BR');

    expect(__('filament-forms::components.select.no_options_message'))
        ->toBe('Nenhuma opção disponível');
});
