<?php

use Esoboy\Ui\Support\Options;

$items = [
    ['value' => 'orders', 'label' => 'Orders', 'children' => [
        ['value' => 'orders.view', 'label' => 'View'],
        ['value' => 'orders.export', 'label' => 'Export', 'disabled' => true],
    ]],
    ['value' => 'settings', 'label' => 'Settings'],
];

it('renders items as an ARIA tree, open where asked', function () use ($items) {
    $html = (string) $this->blade('<x-ui::tree :items="$items" checkable :default-expanded="[\'orders\']" :default-value="[\'orders.view\']" />', ['items' => $items]);

    expect($html)
        ->toContain('<eso-tree role="tree" data-slot="tree" data-checkable="data-checkable" data-value="[&quot;orders.view&quot;]" aria-multiselectable="true">')
        ->toContain('<li role="treeitem" tabindex="-1" data-slot="tree-item" data-value="orders" aria-expanded="true">')
        ->toContain('<span data-slot="tree-item-label">Orders</span>')
        ->toContain('<input type="checkbox" data-slot="checkbox" data-tree-check tabindex="-1" aria-hidden="true" >')
        ->toContain('<ul role="group" data-slot="tree-group" >')
        ->toContain('data-value="orders.export" aria-disabled="true"')
        ->toContain('<li role="treeitem" tabindex="-1" data-slot="tree-item" data-value="settings">');
});

it('composes items with icons, collapsed by default', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::tree default-value="a.txt">
            <x-ui::tree.item value="docs" label="docs">
                <x-slot:icon>F</x-slot:icon>
                <x-ui::tree.item value="a.txt" label="a.txt" />
            </x-ui::tree.item>
        </x-ui::tree>
        BLADE);

    expect($html)
        ->toContain('data-value="&quot;a.txt&quot;"')
        ->toContain('data-value="docs" aria-expanded="false"')
        ->toContain('<span data-slot="tree-item-icon">F</span>')
        ->toContain('<ul role="group" data-slot="tree-group"  hidden >')
        ->not->toContain('data-tree-check');
});

it('renders a tree select with the chosen labels', function () use ($items) {
    $html = (string) $this->blade('<x-ui::tree-select :items="$items" checkable :default-value="[\'orders.view\', \'settings\']" placeholder="Pick" class="w-64" />', ['items' => $items]);

    expect($html)
        ->toContain('<eso-tree-select data-slot="tree-select" data-placeholder="Pick" data-checkable="data-checkable">')
        ->toContain('<button class="w-64" type="button" data-slot="select-trigger" data-tree-select-trigger')
        ->toContain('<span data-slot="select-value">View, Settings</span>')
        ->toContain('popover="auto" data-slot="tree-select-content"')
        ->toContain('<eso-tree role="tree"');

    expect(Options::find($items, ['settings', 'orders.view']))->toBe(['View', 'Settings']);
});
