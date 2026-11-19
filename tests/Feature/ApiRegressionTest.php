<?php

use Illuminate\Support\Facades\Blade;

it('preserves raw typed state through public Blade calls', function (mixed $value) {
    $html = Blade::render('<x-ui::select id="raw-value" :initial-value="$value" :options="[[\'value\' => $value, \'label\' => \'Selected\']]" />', compact('value'));
    expect($html)->toContain('aria-selected="true"');
})->with([0, 'a&b', '<value>', '0']);

it('preserves HTML and entities as editor props without double escaping', function () {
    $value = '<p>A &amp; B</p>';
    $html = Blade::render('<x-ui::editor id="raw-editor" :value="$value" />', compact('value'));
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    foreach ($dom->getElementsByTagName('div') as $element) {
        if ($element->hasAttribute('data-ui-control')) {
            $data = json_decode($element->getAttribute('data-ui-control'), true, flags: JSON_THROW_ON_ERROR);
            expect($data['value'])->toBe($value);

            return;
        }
    }
    test()->fail('Missing native control configuration.');
});

it('uses native anchors for button and text links and removes disabled destinations', function () {
    $html = Blade::render('<x-ui::button link="/docs">Docs</x-ui::button><x-ui::button link="/delete" disabled>Disabled</x-ui::button><x-ui::text link="/about">About</x-ui::text>');
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $links = $dom->getElementsByTagName('a');
    expect($links->length)->toBe(3)
        ->and($links->item(0)->getAttribute('href'))->toBe('/docs')
        ->and($links->item(1)->hasAttribute('href'))->toBeFalse()
        ->and($links->item(1)->getAttribute('aria-disabled'))->toBe('true')
        ->and($links->item(2)->getAttribute('href'))->toBe('/about');
});
