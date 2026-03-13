<?php

use Illuminate\Support\Facades\Route;

// Cette route affiche la page d'accueil par défaut de Laravel
Route::get('/', function () {
    return view('welcome');
});
