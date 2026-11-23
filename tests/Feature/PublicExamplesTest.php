<?php

use Dom\HTMLDocument;
use Illuminate\Support\Facades\Blade;

it('renders the documented component with its public root class', function (string $name, string $example) {
    $example = preg_replace_callback(
        '/(<x-ui::'.preg_quote($name, '/').'(?=[\s\/>]))((?:[^">]|"[^"]*")*)>/',
        static function (array $match): string {
            $attributes = preg_replace('/(\sclass=")/', '$1application-instance ', $match[2], 1, $existingClass);

            return $match[1].($existingClass ? '' : ' class="application-instance"').$attributes.'>';
        },
        $example,
        1,
    );
    $document = HTMLDocument::createFromString(Blade::render($example), LIBXML_NOERROR);

    expect($document->querySelector('body > .ui-'.$name.'.application-instance'))->not->toBeNull()
        ->and($document->querySelector('[data-esoboy-ui], [data-slot]'))->toBeNull();
})->with(function () {
    foreach (json_decode(file_get_contents(__DIR__.'/../../docs/examples.json'), true, flags: JSON_THROW_ON_ERROR) as $name => $example) {
        yield $name => [$name, $example];
    }
});

it('reserves unprefixed component names for the host', function () {
    expect(fn () => Blade::compileString('<x-button />'))
        ->toThrow(InvalidArgumentException::class, 'Unable to locate a class or view for component [button]');
});
