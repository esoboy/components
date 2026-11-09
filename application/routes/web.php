<?php

use App\Editor\DemoMediaUploadController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::index');
Route::post('/demo/editor/media', DemoMediaUploadController::class)->name('demo.editor.media');

foreach (collect(config('demo'))->collapse()->keys() as $page) {
    Route::livewire("/{$page}", "pages::{$page}");
}
