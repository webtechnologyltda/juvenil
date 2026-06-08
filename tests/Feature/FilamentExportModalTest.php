<?php

it('localizes native Filament export modal controls in Portuguese', function () {
    app()->setLocale('pt_BR');

    expect(__('filament-actions::export.modal.form.columns.actions.select_all.label'))
        ->toBe('Selecionar todas')
        ->and(__('filament-actions::export.modal.form.columns.actions.deselect_all.label'))
        ->toBe('Desmarcar todas')
        ->and(__('filament-actions::export.notifications.no_columns.title'))
        ->toBe('Nenhuma coluna selecionada')
        ->and(__('filament-actions::export.notifications.no_columns.body'))
        ->toBe('Selecione pelo menos uma coluna para exportar.');
});
