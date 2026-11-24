<?php

use Dom\HTMLDocument;
use Esoboy\Ui\Livewire\Tree;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('passes group sizes to their own controls and preserves explicit and native sizes', function () {
    $html = Blade::render(<<<'BLADE'
        <x-ui::card size="lg">
            <x-ui::button id="outside">Outside</x-ui::button>
            <x-ui::button-group size="sm">
                <x-ui::button id="inherited">Compact</x-ui::button>
                <x-ui::button.text id="label">Compact</x-ui::button.text>
                <x-ui::input id="field" size="24" />
                <x-ui::input-group id="input-group"><x-ui::input-group.input /></x-ui::input-group>
                <x-ui::native-select id="native"><x-ui::native-select.option>A</x-ui::native-select.option></x-ui::native-select>
                <x-ui::select id="select" :options="[]" />
                <x-ui::button id="explicit" size="default">Default</x-ui::button>
                <x-ui::button-group><x-ui::button id="nested">Nested default</x-ui::button></x-ui::button-group>
            </x-ui::button-group>
        </x-ui::card>
        <x-ui::input size="lg" id="visual" />
        <x-ui::password-input size="20" id="password" />
        BLADE);
    $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    foreach (['outside' => 'default', 'inherited' => 'sm', 'label' => 'sm', 'explicit' => 'default', 'nested' => 'default'] as $id => $size) {
        expect($doc->getElementById($id)->classList->contains('ui-button-size-'.$size))->toBeTrue();
    }
    expect($doc->getElementById('field')->getAttribute('size'))->toBe('24')
        ->and($doc->getElementById('field')->getAttribute('data-size'))->toBe('sm')
        ->and($doc->getElementById('input-group')->getAttribute('data-size'))->toBe('sm')
        ->and($doc->getElementById('native')->getAttribute('data-size'))->toBe('sm')
        ->and($doc->getElementById('select')->getAttribute('data-size'))->toBe('sm')
        ->and($doc->getElementById('visual')->hasAttribute('size'))->toBeFalse()
        ->and($doc->getElementById('visual')->getAttribute('data-size'))->toBe('lg')
        ->and($doc->getElementById('password')->getAttribute('size'))->toBe('20');
});

it('sizes generated and slotted pagination without changing the page window', function () {
    $pager = new LengthAwarePaginator([], 120, 10, 6);
    $html = Blade::render(<<<'BLADE'
        <x-ui::pagination id="generated" size="sm" :paginator="$pager" :max-pages="3" jumpable />
        <x-ui::pagination id="slotted" size="lg">
            <x-ui::pagination.previous />
            <x-ui::pagination.link>1</x-ui::pagination.link>
            <x-ui::pagination.button size="xs">Custom</x-ui::pagination.button>
        </x-ui::pagination>
        BLADE, compact('pager'));
    $doc = HTMLDocument::createFromString($html, LIBXML_NOERROR);
    expect($doc->querySelectorAll('#generated .ui-button-size-icon-sm')->length)->toBe(3)
        ->and($doc->querySelectorAll('#generated .ui-pagination-content .ui-button-size-sm')->length)->toBe(2)
        ->and($doc->querySelector('#generated .ui-input-group')->getAttribute('data-size'))->toBe('sm')
        ->and($doc->querySelector('#slotted .ui-pagination-previous.ui-button-size-lg'))->not->toBeNull()
        ->and($doc->querySelector('#slotted .ui-pagination-link.ui-button-size-icon-lg'))->not->toBeNull()
        ->and($doc->querySelector('#slotted .ui-pagination-button.ui-button-size-xs'))->not->toBeNull();
});

it('keeps the requested size on the rendered control after a Livewire update', function () {
    Livewire::test(Tree::class, [
        'size' => 'lg', 'nodes' => [['value' => 'parent', 'label' => 'Parent', 'children' => [['value' => 'child', 'label' => 'Child']]]],
    ])->call('expand', 'parent')->assertSee('Child')->assertSee('ui-button-size-lg', false);
});
