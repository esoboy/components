<?php

/*
 * Resizable on the Layout page: handles resize their panels from the keyboard and the
 * pointer, within their panels' limits.
 */

const HANDLE = '[data-slot="resizable-handle"] >> nth=0';

it('resizes its panels from the keyboard', function () {
    visit('/layout')
        ->assertAttribute(HANDLE, 'aria-valuenow', '50')
        ->keys(HANDLE, 'ArrowRight')
        ->assertScript("Number(document.querySelector('[data-slot=\"resizable-handle\"]').getAttribute('aria-valuenow')) > 50")
        ->keys(HANDLE, 'Home')
        ->assertAttribute(HANDLE, 'aria-valuenow', '0')
        ->assertNoJavaScriptErrors();
});

it('resizes its panels by dragging the handle', function () {
    $page = visit('/layout');

    $handle = $page->page()->locator('[data-slot="resizable-handle"]')->first();
    $group = $page->page()->locator('[data-slot="resizable-panel-group"]')->first();
    $box = $group->boundingBox();

    $handle->dragTo($group, ['targetPosition' => ['x' => $box['width'] * 0.3, 'y' => $box['height'] / 2]]);

    $page->assertScript("Math.abs(Number(document.querySelector('[data-slot=\"resizable-handle\"]').getAttribute('aria-valuenow')) - 30) <= 2");
});

it('keeps a panel above its minimum size', function () {
    visit('/layout')
        ->keys('[data-slot="resizable-handle"] >> nth=1', 'Home')
        ->assertAttribute('[data-slot="resizable-handle"] >> nth=1', 'aria-valuenow', '15');
});
