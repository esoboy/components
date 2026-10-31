<?php

/*
 * Signature on the Inputs page: drawing on the pad binds a data URL, PNG or SVG.
 */

/**
 * Draws a stroke across the pad with the mouse.
 */
function sign(mixed $page, string $selector): void
{
    $canvas = $page->page()->locator("{$selector} [data-slot=\"signature-canvas\"]");
    $box = $canvas->boundingBox();

    $canvas->dragTo($canvas, [
        'sourcePosition' => ['x' => $box['width'] * 0.2, 'y' => $box['height'] * 0.6],
        'targetPosition' => ['x' => $box['width'] * 0.8, 'y' => $box['height'] * 0.4],
    ]);
}

it('binds an SVG data URL as it is drawn', function () {
    $page = visit('/inputs')->assertSeeIn('#initials-size', 'empty');

    sign($page, '#initials');

    $page->assertSeeIn('#initials-size', 'KB')
        ->assertNoJavaScriptErrors();
});

it('sends a PNG with the form and clears from the server', function () {
    $page = visit('/inputs');

    sign($page, '#signature');

    $page->click('Save')
        ->assertSee('Signature saved')
        ->assertAttributeContains('#saved-signature', 'src', 'data:image/png;base64,')
        ->click('Reset from server')
        ->assertScript("document.querySelector('#signature').value === null");
});

it('requires a signature to save', function () {
    visit('/inputs')
        ->click('Save')
        ->assertSee('The signature field is required.');
});
