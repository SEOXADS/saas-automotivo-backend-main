<?php

use Illuminate\Support\Facades\Route;

// Aplicar middleware de redirect em todas as rotas web
Route::middleware(['url.redirect'])->group(function () {
    Route::get('/', function () {
        return view('welcome');
    });
});
