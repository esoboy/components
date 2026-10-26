<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::livewire('/button', 'pages::button');
Route::livewire('/appearance', 'pages::appearance');
Route::livewire('/forms', 'pages::forms');
