<?php

/*
 * Transfer on the Inputs page: checked items move between the lists; the value is the
 * right list, in the options' order.
 */

const SOURCE = '#roles [data-slot="transfer-list"][data-list="source"]';

const TARGET = '#roles [data-slot="transfer-list"][data-list="target"]';

it('moves checked items to the right list', function () {
    visit('/inputs')
        ->assertSeeIn('#roles-value', 'editor')
        ->assertDisabled('#roles [data-transfer-move="target"]')
        ->click(SOURCE.' [data-slot="transfer-item"][data-value="admin"]')
        ->click(SOURCE.' [data-slot="transfer-item"][data-value="viewer"]')
        ->click('#roles [data-transfer-move="target"]')
        ->assertSeeIn('#roles-value', 'admin, editor, viewer')
        ->assertPresent(TARGET.' [data-slot="transfer-item"][data-value="admin"]')
        ->assertNoJavaScriptErrors();
});

it('moves items back to the left list', function () {
    visit('/inputs')
        ->click(TARGET.' [data-slot="transfer-item"][data-value="editor"]')
        ->click('#roles [data-transfer-move="source"]')
        ->assertSeeIn('#roles-value', 'none');
});

it('checks the shown items from its header and filters by search', function () {
    visit('/inputs')
        ->type(SOURCE.' [data-transfer-search]', 'bil')
        ->assertScript("[...document.querySelectorAll('".SOURCE." [data-slot=\"transfer-item\"]')].filter(item => item.checkVisibility()).map(item => item.dataset.value).join()", 'billing')
        ->click(SOURCE.' [data-transfer-all]')
        ->click('#roles [data-transfer-move="target"]')
        ->assertSeeIn('#roles-value', 'editor, billing');
});

it('leaves disabled items in place', function () {
    visit('/inputs')
        ->click(SOURCE.' [data-transfer-all]')
        ->click('#roles [data-transfer-move="target"]')
        ->assertPresent(SOURCE.' [data-slot="transfer-item"][data-value="owner"]')
        ->assertDontSeeIn('#roles-value', 'owner');
});
