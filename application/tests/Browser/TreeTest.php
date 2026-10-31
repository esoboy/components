<?php

/*
 * Tree on the Inputs page: checkable with parents following children, single selection
 * with search, children loaded from the server, and drag to reorder.
 */

const PERMISSIONS = 'eso-tree[wire\\:model\\.live="permissions"]';

function treeItem(string $tree, string $value): string
{
    return "{$tree} [data-slot=\"tree-item\"][data-value=\"{$value}\"]";
}

it('checks a parent with all its enabled children', function () {
    visit('/inputs')
        ->click(treeItem(PERMISSIONS, 'orders').' > [data-slot="tree-item-row"] [data-slot="checkbox"]')
        ->assertScript("document.getElementById('permissions').textContent.split(', ').sort().join()", 'orders,orders.refund,orders.view,users,users.ban,users.view')
        ->assertAttribute(treeItem(PERMISSIONS, 'orders.export'), 'aria-disabled', 'true')
        ->assertNoJavaScriptErrors();
});

it('selects one item and filters by search', function () {
    visit('/inputs')
        ->click('[data-slot="tree-item-label"]:text-is("card.blade.php")')
        ->assertSeeIn('#file', 'card.blade.php')
        ->type('eso-tree[wire\\:model\\.live="file"] input', 'layout')
        ->assertScript("[...document.querySelectorAll('eso-tree[wire\\\\:model\\\\.live=\"file\"] [data-slot=\"tree-item-label\"]')].filter(label => label.checkVisibility()).map(label => label.textContent.trim()).join()", 'components,layout.blade.php');
});

it('loads children from the server when an item opens', function () {
    visit('/inputs')
        ->click(treeItem('#org-tree', 'eng').' > [data-slot="tree-item-row"] [data-slot="tree-item-toggle"]')
        ->assertSeeIn('#org-tree', 'Web')
        ->assertSeeIn('#org-tree', 'Mobile')
        ->assertAttribute(treeItem('#org-tree', 'eng'), 'aria-expanded', 'true');
});

it('checks loaded children of a checked parent', function () {
    visit('/inputs')
        ->click(treeItem('#org-tree', 'design').' > [data-slot="tree-item-row"] [data-slot="tree-item-toggle"]')
        ->assertSeeIn('#org-tree', 'Brand')
        ->assertScript("['design.brand', 'design.product'].every(value => document.getElementById('staff').textContent.split(', ').includes(value))");
});
