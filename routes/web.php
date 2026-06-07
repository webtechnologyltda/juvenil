<?php

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
    return response()->file(asset('pdf/' . $filename));
})->name('pdf.show');

Route::redirect('/inscricao-equipe-trabalho', '/campista')->name('inscricao-equipe-trabalho');
