<?php

use Illuminate\Support\Facades\Blade;

it('renders the documented public call', function (string $example) {
    expect(Blade::render($example))->not->toBe('');
})->with(json_decode(file_get_contents(__DIR__.'/../../docs/examples.json'), true, flags: JSON_THROW_ON_ERROR));

it('reserves unprefixed component names for the host', function () {
    expect(fn () => Blade::compileString('<x-button />'))
        ->toThrow(InvalidArgumentException::class, 'Unable to locate a class or view for component [button]');
});
