<?php

use App\Models\User;

/*
 * Every demo page renders and runs its scripts without errors.
 */

beforeEach(function () {
    User::factory()->count(12)->create();
});

it('renders every demo page without script errors', function (string $path) {
    visit($path)
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
})->with(fn () => ['/', ...collect(require __DIR__.'/../../config/demo.php')->collapse()->keys()->map(fn (string $page) => "/{$page}")]);
