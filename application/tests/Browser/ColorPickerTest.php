<?php

/*
 * Color Picker on the Inputs page: swatches, the hex input and the area set the bound
 * color.
 */

const COLOR_PICKER = '#brand-color';

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

it('moves the color with the arrow keys on its area', function () {
    visit('/inputs')
        ->click(COLOR_PICKER.' [data-color-picker-trigger]')
        ->keys(COLOR_PICKER.' [data-slot="color-picker-area"]', 'ArrowDown')
        ->assertScript("/^#[0-9a-f]{6}$/.test(document.getElementById('color').textContent.trim()) && document.getElementById('color').textContent.trim() !== '#2563eb'");
});
