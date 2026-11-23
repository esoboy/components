<?php

use Dom\HTMLDocument;
use Esoboy\Ui\Support\ComponentColor;
use Illuminate\Support\Facades\Blade;

it('exposes an instance color through the public Blade component', function (string $name) {
    $examples = json_decode(file_get_contents(__DIR__.'/../../docs/examples.json'), true, flags: JSON_THROW_ON_ERROR);
    $example = $examples[$name] ?? "<x-ui::$name>Item</x-ui::$name>";
    $example = preg_replace('/<x-ui::'.preg_quote($name, '/').'(?=[\s>\/])/', '$0 color="var(--brand)"', $example, 1);
    $html = Blade::render($example);

    // Date and time panels are initially inside a Livewire teleport template.
    expect($html)->toContain('--ui-color: var(--brand)', 'data-ui-color=')
        ->not->toMatch('/\scolor="var\(--brand\)"/');
})->with([
    'button', 'button.text', 'badge', 'alert', 'toast', 'bubble', 'marker', 'marker.button',
    'text', 'heading', 'label', 'icon', 'spinner', 'statistic', 'avatar.fallback', 'avatar.badge', 'avatar.group-count',
    'checkbox', 'radio-group', 'radio-group.item', 'switch', 'toggle', 'toggle-group', 'toggle-group.item',
    'progress', 'rating', 'slider', 'steps', 'timeline', 'timeline.item', 'tabs', 'pagination', 'carousel', 'signature', 'qr-code',
    'select', 'combobox', 'icon-select', 'command', 'tags-input', 'tree', 'tree-select', 'cascader', 'questionnaire',
    'calendar', 'date-picker', 'datetime-picker', 'time-picker',
    'dropdown-menu.item', 'dropdown-menu.link', 'context-menu.item', 'context-menu.link', 'menubar.item', 'menubar.link',
]);

it('merges caller styles and leaves the default and slotted components independently themed', function () {
    $html = Blade::render(<<<'BLADE'
        <x-ui::alert color="#ee5500" style="--host: 1">
            <x-ui::button id="custom" link="/save" variant="outline" color="var(--brand, #369)" style="margin: 1px">Save</x-ui::button>
            <x-ui::button id="default">Cancel</x-ui::button>
        </x-ui::alert>
        BLADE);
    $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    expect($document->querySelector('[role=alert]')->getAttribute('style'))->toContain('--host: 1', '--ui-color: #ee5500')
        ->and($document->querySelector('a#custom')->getAttribute('style'))->toContain('margin: 1px', '--ui-color: var(--brand, #369)')
        ->and($document->querySelector('#default')->hasAttribute('data-ui-color'))->toBeFalse();
});

it('accepts one CSS color value', function (string $color) {
    expect(ComponentColor::attributes($color, 'ink')->get('style'))->toContain($color);
})->with(['red', '#1234', '#112233aa', 'rgb(20 40 60 / 75%)', 'oklch(65% 0.18 245)', 'var(--Brand_accent)', 'color-mix(in oklab, var(--theme, #369) 60%, white)']);

it('rejects values that introduce another declaration', function () {
    expect(fn () => ComponentColor::attributes('red; display:none', 'ink'))->toThrow(InvalidArgumentException::class);
});

it('resolves palette names and opacity against the host theme', function () {
    $color = ComponentColor::attributes('brand-blue-750/37.5', 'button');
    expect($color->get('data-ui-palette'))->toBe('brand-blue-750')
        ->and($color->get('style'))->toContain('var(--color-brand-blue-750,', '37.5%, transparent');
    expect(fn () => ComponentColor::attributes('blue-500/101', 'ink'))->toThrow(InvalidArgumentException::class);
});
