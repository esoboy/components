<?php

/*
 * Tree Select on the Inputs page: a Select-style trigger opening a Tree.
 */

const TREE_SELECT = 'eso-tree-select[wire\\:model\\.live="department"]';

it('picks one item and shows it on its trigger', function () {
    visit('/inputs')
        ->click(TREE_SELECT.' [data-slot="select-trigger"]')
        ->assertScript(opened(TREE_SELECT.' [data-slot="tree-select-content"]'))
        ->click(TREE_SELECT.' [data-slot="tree-item"][data-value="web"] > [data-slot="tree-item-row"]')
        ->assertSeeIn('#department', 'web')
        ->assertSeeIn(TREE_SELECT.' [data-slot="select-value"]', 'Web')
        ->assertNoJavaScriptErrors();
});

it('checks several items when checkable', function () {
    $select = 'eso-tree-select[wire\\:model\\.live="departments"]';

    visit('/inputs')
        ->click($select.' [data-slot="select-trigger"]')
        ->click($select.' [data-slot="tree-item"][data-value="sales"] > [data-slot="tree-item-row"]')
        ->click($select.' [data-slot="tree-item"][data-value="mobile"] > [data-slot="tree-item-row"]')
        ->assertScript("document.getElementById('departments').textContent.split(', ').sort().join()", 'mobile,sales');
});
