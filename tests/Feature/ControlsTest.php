<?php

use Esoboy\Ui\Livewire\Calendar;
use Esoboy\Ui\Livewire\Combobox;
use Esoboy\Ui\Livewire\TagsInput;
use Esoboy\Ui\Livewire\Transfer;
use Esoboy\Ui\Livewire\Tree;
use Esoboy\Ui\Support\TreeNodes;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('renders all new stateful public entries without client templates', function (string $name) {
    $html = Blade::render('<x-ui::'.$name.' id="example" />');
    expect($html)->toContain('wire:snapshot=')->not->toContain('x-for=')->not->toContain('x-html=')->not->toContain('x-text=');
})->with(['select', 'combobox', 'command', 'tags-input', 'calendar', 'date-picker', 'datetime-picker', 'time-picker', 'tree', 'tree-select', 'cascader', 'transfer']);

it('forwards native input group attributes to the input', function () {
    $html = Blade::render('<x-ui::input-group.input id="email" wire:model="email" aria-describedby="email-hint" />');
    $dom = new DOMDocument;
    $dom->loadHTML($html);
    $input = $dom->getElementsByTagName('input')->item(0);
    expect($input->getAttribute('id'))->toBe('email')
        ->and($input->getAttribute('wire:model'))->toBe('email')
        ->and($input->getAttribute('aria-describedby'))->toBe('email-hint')
        ->and(explode(' ', $input->getAttribute('class')))->toContain('ui-input-group-control');
});

it('keeps the selection while editing a combobox and restores its label when editing ends', function () {
    Livewire::test(Combobox::class, ['initialValue' => 'a', 'options' => [['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']]])
        ->assertSet('query', 'Alpha')
        ->set('query', 'bet')->assertSee('Beta')->assertSet('value', 'a')->assertSet('query', 'bet')
        ->call('finishSearch')->assertSet('query', 'Alpha')->assertSee('Alpha')->assertSee('Beta')
        ->call('choose', 'b')->assertSet('value', 'b')->assertSet('query', 'Beta')
        ->call('clearSelection')->assertSet('value', null)->assertSet('query', '');
});

it('preserves combobox chip selection order and removes the last selected option', function () {
    $options = [['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta'], ['value' => 'c', 'label' => 'Gamma']];
    Livewire::test(Combobox::class, ['multiple' => true, 'initialValue' => ['c', 'a'], 'options' => $options])
        ->assertSet('label', 'Gamma, Alpha')->assertSet('query', '')
        ->call('choose', 'b')->assertSet('label', 'Gamma, Alpha, Beta')
        ->call('removeLast')->assertSet('value', ['c', 'a'])
        ->call('clearSelection')->assertSet('value', [])->assertSet('label', '');
});

it('deduplicates tags and enforces limits', function () {
    Livewire::test(TagsInput::class, ['initialValue' => ['first'], 'max' => 2])
        ->set('draft', ' first ')->call('add')->assertSet('value', ['first'])
        ->set('draft', 'second')->call('add')->assertSet('value', ['first', 'second'])
        ->set('draft', 'third')->call('add')->assertSet('value', ['first', 'second'])
        ->call('remove', 'first')->assertSet('value', ['second']);
});

it('rejects impossible and out of range dates', function () {
    Livewire::test(Calendar::class, ['initialValue' => '2024-02-29', 'min' => '2024-02-01', 'max' => '2024-03-31'])
        ->call('choose', '2025-02-29')->assertSet('value', '2024-02-29')
        ->call('choose', '2024-04-01')->assertSet('value', '2024-02-29')
        ->call('moveMonth', 1)->assertSet('month', '2024-03-01')
        ->call('choose', '2024-03-31')->assertSet('value', '2024-03-31');
});

it('expands a tree and selects a typed leaf', function () {
    Livewire::test(Tree::class, ['nodes' => [['value' => 'root', 'label' => 'Parent', 'children' => [['value' => 0, 'label' => 'Child']]]]])
        ->assertDontSee('Child')->call('expand', 'root')->assertSee('Child')
        ->call('choose', 0)->assertSet('value', 0)->call('expand', 'root')->assertDontSee('Child');
});

it('indexes large trees once and rejects duplicate identities', function () {
    $nodes = array_map(fn (int $i): array => ['value' => $i, 'label' => "Node {$i}"], range(1, 2000));
    expect(TreeNodes::flatten($nodes))->toHaveCount(2000);
    expect(fn () => TreeNodes::flatten([['value' => 1, 'label' => 'A'], ['value' => 1, 'label' => 'B']]))->toThrow(InvalidArgumentException::class);
});

it('moves only enabled transfer options', function () {
    Livewire::test(Transfer::class, ['options' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B', 'disabled' => true]]])
        ->call('moveAll', true)->assertSet('value', ['a'])->call('moveAll', false)->assertSet('value', []);
});

it('does not retain tree results between repeated renders even before cycle collection', function () {
    $nodes = array_map(fn (int $i): array => ['value' => $i, 'label' => "Node {$i}"], range(1, 1000));
    TreeNodes::flatten($nodes);
    gc_collect_cycles();
    $enabled = gc_enabled();
    gc_disable();
    $start = memory_get_usage();
    try {
        for ($i = 0; $i < 20; $i++) {
            TreeNodes::flatten($nodes);
        }
        $retained = memory_get_usage() - $start;
    } finally {
        if ($enabled) {
            gc_enable();
        }
    }
    expect($retained)->toBeLessThan(512 * 1024);
});

it('accepts exactly 64 tree levels and rejects deeper input', function () {
    $nodes = [];
    for ($level = 64; $level > 0; $level--) {
        $nodes = [['value' => $level, 'label' => "Level {$level}", 'children' => $nodes]];
    }
    expect(TreeNodes::flatten($nodes))->toHaveCount(64);
    expect(fn () => TreeNodes::flatten([['value' => 0, 'label' => 'Extra', 'children' => $nodes]]))
        ->toThrow(InvalidArgumentException::class);
});
