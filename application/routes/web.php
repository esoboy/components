<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::livewire('/button', 'pages::button');
Route::livewire('/forms', 'pages::forms');
Route::livewire('/overlays', 'pages::overlays');
