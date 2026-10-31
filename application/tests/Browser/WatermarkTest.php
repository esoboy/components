<?php

/*
 * Watermark on the Layout page: the layer follows the bound text and comes back when a
 * script removes or hides it.
 */

const LAYER = '[data-slot="watermark"] [data-slot="watermark-layer"]';

it('puts its layer back when it is removed or hidden', function () {
    $page = visit('/layout')->assertPresent(LAYER);

    $page->script("document.querySelector('".LAYER."').remove()");
    $page->assertPresent(LAYER);

    $page->script("document.querySelector('".LAYER."').style.display = 'none'");
    $page->assertScript("getComputedStyle(document.querySelector('".LAYER."')).display !== 'none'")
        ->assertNoJavaScriptErrors();
});

it('redraws its tile when the server changes the text', function () {
    $page = visit('/layout');

    $before = $page->script("document.querySelector('".LAYER."').style.getPropertyValue('--watermark-image')");

    $page->fill('[data-slot="input"][wire\\:model\\.live\\.debounce\\.300ms="viewer"]', 'Ada Lovelace')
        ->assertScript("document.querySelector('".LAYER."').style.getPropertyValue('--watermark-image') !== ".json_encode($before));
});
