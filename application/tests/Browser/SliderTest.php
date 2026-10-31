<?php

/*
 * Slider on the Controls page: native range thumbs bound with wire:model, a number for
 * one thumb and a list for two, which can't cross.
 */

it('binds one thumb from the keyboard', function () {
    visit('/controls')
        ->assertSeeIn('#volume', '50')
        ->keys('[wire\\:model\\.live="volume"] [data-slot="slider-thumb"]', ['ArrowRight', 'ArrowRight'])
        ->assertSeeIn('#volume', '52')
        ->assertNoJavaScriptErrors();
});

it('binds two thumbs as a list by their step', function () {
    visit('/controls')
        ->assertSeeIn('#price', '25-75')
        ->keys('[wire\\:model\\.live="price"] [data-slot="slider-thumb"] >> nth=1', 'ArrowLeft')
        ->assertSeeIn('#price', '25-70');
});

it('keeps a thumb from crossing the other', function () {
    visit('/controls')
        ->keys('[wire\\:model\\.live="price"] [data-slot="slider-thumb"] >> nth=0', 'End')
        ->assertSeeIn('#price', '75-75');
});

it('follows the pointer along its track', function () {
    $page = visit('/controls');

    $box = $page->page()->locator('[wire\\:model\\.live="volume"]')->boundingBox();

    $page->page()->locator('[wire\\:model\\.live="volume"]')->click(['position' => ['x' => $box['width'] * 0.8, 'y' => $box['height'] / 2]]);

    $page->assertScript("Math.abs(Number(document.getElementById('volume').textContent) - 80) <= 2");
});
