<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::livewire('/button', 'pages::button');
Route::livewire('/forms', 'pages::forms');
Route::livewire('/overlays', 'pages::overlays');
Route::livewire('/display', 'pages::display');
Route::livewire('/controls', 'pages::controls');
Route::livewire('/admin', 'pages::admin');
Route::livewire('/more', 'pages::more');
Route::livewire('/chat', 'pages::chat');
Route::livewire('/extras', 'pages::extras');
Route::livewire('/uploads', 'pages::uploads');
Route::livewire('/table', 'pages::table');
Route::livewire('/inputs', 'pages::inputs');
Route::livewire('/details', 'pages::details');
