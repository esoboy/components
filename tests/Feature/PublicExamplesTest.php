<?php

use Illuminate\Support\Facades\Blade;

it('renders the documented public call', function (string $example) {
    expect(Blade::render($example))->not->toBe('');
})->with(json_decode(file_get_contents(__DIR__.'/../../docs/examples.json'), true, flags: JSON_THROW_ON_ERROR));
