<?php

use App\Http\Controllers\Admin\LancamentoReceiptController;
use App\Http\Controllers\Admin\PrintableReportController;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Route;

$campistaRegistrationPage = function () {
    $settings = app(GeneralSettings::class);

    return view('welcome', compact('settings'));
};

Route::get('/', $campistaRegistrationPage)->name('welcome');
Route::get('/campista', $campistaRegistrationPage)->name('campista');

Route::get('/politica-privacidade', function () {
    return view('politica-privacidade');
})->name('politica-privacidade');

Route::get('/termos-inscricao', function () {
    return view('termos-inscricao');
})->name('termos-inscricao');

Route::get('/pdf/{filename}', function ($filename) {
    return response()->file(asset('pdf/'.$filename));
})->name('pdf.show');

Route::middleware('auth')
    ->prefix('admin/relatorios')
    ->name('admin.reports.')
    ->group(function () {
        Route::get('/imprimir', PrintableReportController::class)->name('print');
    });

Route::middleware(['auth', 'signed'])
    ->prefix('admin/lancamentos')
    ->name('admin.lancamentos.')
    ->group(function () {
        Route::get('/{lancamento}/comprovante', LancamentoReceiptController::class)->name('comprovantes.show');
    });

Route::redirect('/inscricao-equipe-trabalho', '/campista')->name('inscricao-equipe-trabalho');
