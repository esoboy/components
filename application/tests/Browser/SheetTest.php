<?php

/*
 * Sheet on the Overlays page: a modal dialog against each edge of the window.
 */

it('opens against its side and closes', function (string $side) {
    $edges = [
        'top' => 'rect.top <= 0',
        'right' => 'rect.right >= innerWidth',
        'bottom' => 'rect.bottom >= innerHeight',
        'left' => 'rect.left <= 0',
    ];

    visit('/overlays')
        ->click("[commandfor=\"sheet-{$side}\"][command=\"show-modal\"]")
        ->assertScript(opened("#sheet-{$side}"))
        ->assertScript("(() => { const rect = document.getElementById('sheet-{$side}').getBoundingClientRect(); return {$edges[$side]} })()")
        ->click("#sheet-{$side} [data-slot=\"sheet-footer\"] button")
        ->assertScript(opened("#sheet-{$side}"), false)
        ->assertNoJavaScriptErrors();
})->with(['top', 'right', 'bottom', 'left']);
