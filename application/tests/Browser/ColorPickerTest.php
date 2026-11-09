<?php

/*
 * Color Picker on the Inputs page: swatches, the hex input and the area set the bound
 * color.
 */

const COLOR_PICKER = 'eso-color-picker:has(#brand-color)';

it('picks a swatch', function () {
    visit('/inputs')
        ->click(COLOR_PICKER.' [data-color-picker-trigger]')
        ->assertScript(opened(COLOR_PICKER.' [data-color-picker-content]'))
        ->click(COLOR_PICKER.' [data-color-picker-swatch="#ef4444"]')
        ->assertSeeIn('#color', '#ef4444')
        ->assertSeeIn(COLOR_PICKER.' [data-slot="color-picker-value"]', '#ef4444')
        ->assertNoJavaScriptErrors();
});

it('takes a typed hex color', function () {
    visit('/inputs')
        ->click(COLOR_PICKER.' [data-color-picker-trigger]')
        ->fill(COLOR_PICKER.' [data-color-picker-input]', '#123456')
        ->keys(COLOR_PICKER.' [data-color-picker-input]', 'Enter')
        ->assertSeeIn('#color', '#123456');
});

it('renders a color supplied by the parent and clears it through the field', function () {
    $page = visit('/inputs');
    $page->script(<<<'JS'
        (() => {
            const root = document.querySelector('eso-color-picker:has(#brand-color)').parentElement.closest('[wire\\:id]');
            Livewire.find(root.getAttribute('wire:id')).$set('color', 'f80');
        })()
        JS);
    $page->assertSeeIn(COLOR_PICKER.' [data-slot="color-picker-value"]', '#ff8800')
        ->click(COLOR_PICKER.' [data-color-picker-trigger]')
        ->fill(COLOR_PICKER.' [data-color-picker-input]', '')
        ->keys(COLOR_PICKER.' [data-color-picker-input]', 'Enter')
        ->assertSeeIn('#color', 'none')
        ->assertSeeIn(COLOR_PICKER.' [data-slot="color-picker-value"]', 'Pick a color');
});

it('moves the color with the arrow keys on its area', function () {
    visit('/inputs')
        ->click(COLOR_PICKER.' [data-color-picker-trigger]')
        ->keys(COLOR_PICKER.' [data-slot="color-picker-area"]', 'ArrowDown')
        ->assertScript("/^#[0-9a-f]{6}$/.test(document.getElementById('color').textContent.trim()) && document.getElementById('color').textContent.trim() !== '#2563eb'");
});

it('binds keyboard changes from the shared hue slider', function () {
    $page = visit('/inputs')
        ->click(COLOR_PICKER.' [data-color-picker-trigger]')
        ->fill(COLOR_PICKER.' [data-color-picker-input]', '#ff0000')
        ->keys(COLOR_PICKER.' [data-color-picker-input]', 'Enter')
        ->assertSeeIn('#color', '#ff0000');

    $page->keys(COLOR_PICKER.' [data-color-picker-hue] input', 'End')
        ->keys(COLOR_PICKER.' [data-color-picker-hue] input', 'ArrowLeft')
        ->assertScript("document.querySelector('eso-color-picker:has(#brand-color) [data-color-picker-hue] input').value === '359'")
        ->assertScript("document.getElementById('color').textContent.trim() !== '#ff0000'")
        ->assertNoJavaScriptErrors();
});
