<?php

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->get('/page', fn () => '<!DOCTYPE html><html lang="en"><body>page</body></html>');
    Route::middleware('web')->get('/stamped', fn () => '<html data-appearance="light"><body></body></html>');
    Route::middleware('web')->get('/json', fn () => ['html' => '<html>']);
});

it('stamps the chosen appearance on <html>', function (string $appearance) {
    $this->withUnencryptedCookie('ui_appearance', $appearance)
        ->get('/page')
        ->assertSee('<html lang="en" data-appearance="'.$appearance.'">', false);
})->with(['light', 'dark']);

it('leaves the page alone when following the system', function () {
    $this->get('/page')->assertSee('<html lang="en">', false);
});

it('ignores unknown cookie values', function () {
    $this->withUnencryptedCookie('ui_appearance', '"><script>')
        ->get('/page')
        ->assertSee('<html lang="en">', false);
});

it('keeps an appearance the page set itself', function () {
    $this->withUnencryptedCookie('ui_appearance', 'dark')
        ->get('/stamped')
        ->assertSee('<html data-appearance="light">', false)
        ->assertDontSee('data-appearance="dark"', false);
});

it('only touches html responses', function () {
    $this->withUnencryptedCookie('ui_appearance', 'dark')
        ->get('/json')
        ->assertExactJson(['html' => '<html>']);
});
