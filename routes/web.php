<?php

use App\Livewire\CampistaForm;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $settings = app(\App\Settings\GeneralSettings::class);
//    dd($settings);
//    return view('welcome', compact('settings'));
    return redirect()->route('inscricao-equipe-trabalho');
})->name('welcome');
//Route::get('/campista', CampistaForm::class,);

Route::get('/politica-privacidade', function () {
    return view('politica-privacidade');
})->name('politica-privacidade');

Route::get('/termos-inscricao', function () {
    return view('termos-inscricao');
})->name('termos-inscricao');

Route::get('/pdf/{filename}', function ($filename) {
    return response()->file(asset('pdf/' . $filename));
})->name('pdf.show');

Route::get('/inscricao-equipe-trabalho', function () {
    return view('incricao-equipe-trabalho-page');
})->name('inscricao-equipe-trabalho');
