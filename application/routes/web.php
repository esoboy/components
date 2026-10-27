<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::index');

foreach (collect(config('demo'))->collapse()->keys() as $page) {
    Route::livewire("/{$page}", "pages::{$page}");
}
