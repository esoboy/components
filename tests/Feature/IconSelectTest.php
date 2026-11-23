<?php

use Dom\HTMLDocument;
use Esoboy\Ui\Livewire\IconSelect;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('keeps the rendered list bounded while browsing every packaged icon with a preview', function () {
    $catalog = require __DIR__.'/../../resources/icons/catalog.php';
    $html = Blade::render('<x-ui::icon-select name="icon" initial-value="zoom-out" />');
    $closed = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    expect($closed->querySelector('input[name="icon"]')->getAttribute('value'))->toBe('zoom-out')
        ->and(trim($closed->querySelector('[role="option"][aria-selected="true"]')->textContent))->toBe('zoom-out')
        ->and($html)->not->toContain('a-arrow-down');

    $picker = Livewire::test(IconSelect::class);
    $pageSize = HTMLDocument::createFromString($picker->html(), LIBXML_NOERROR)->querySelectorAll('[role="option"]')->length;
    expect($pageSize)->toBeBetween(1, 48);

    foreach (array_chunk(array_keys($catalog), $pageSize) as $index => $names) {
        if ($index > 0) {
            $picker->call('showResultPage', $index + 1);
        }

        $document = HTMLDocument::createFromString($picker->html(), LIBXML_NOERROR);
        $labels = [];
        foreach ($document->querySelectorAll('[role="option"]') as $option) {
            $labels[] = trim($option->textContent);
        }

        expect($labels)->toBe($names)
            ->and($document->querySelectorAll('[data-slot="option-text"] svg')->length)->toBe(count($names));
    }

    $picker->call('choose', 'zoom-out')->assertSet('value', 'zoom-out')->assertSet('browsePage', null);
    $picker->call('showResultPage', 1)->assertSee('a-arrow-down')
        ->call('finishSearch')->assertSet('value', 'zoom-out')->assertSee('zoom-out')->assertDontSee('a-arrow-down');
});

it('searches the full icon catalog and emits only the chosen name', function () {
    Livewire::test(IconSelect::class, ['initialValue' => 'circle-check'])
        ->call('showResultPage', PHP_INT_MAX)->assertSee('zoom-out')
        ->set('query', 'arrow')->assertSet('resultPage', 1)->assertSee('a-arrow-down')
        ->call('showResultPage', 2)->assertDontSee('a-arrow-down')
        ->set('query', 'no-such-ui-icon')->assertSet('resultPage', 1)->assertDontSee('role="option"', false)
        ->set('query', 'ZOOM-OU')->assertSet('value', 'circle-check')
        ->assertSee('zoom-out')->assertDontSee('a-arrow-down')
        ->call('choose', 'zoom-out')->assertSet('value', 'zoom-out')->assertDispatched('value-changed', value: 'zoom-out')
        ->call('choose', 'no-such-ui-icon')->assertSet('value', 'zoom-out')->assertNotDispatched('value-changed')
        ->call('clearSelection')->assertSet('value', null)->assertDispatched('value-changed', value: null);
});
