<?php

/*
 * Code Block and Trace on the AI page: highlighting loads with its own script, the copy
 * button copies the code, and a trace step collapses.
 */

it('highlights its code', function () {
    visit('/ai')
        ->assertScript("document.querySelector('[data-slot=\"code-block\"] span[style]') !== null")
        ->assertNoJavaScriptErrors();
});

it('copies its code and says so', function () {
    $page = visit('/ai');

    $page->script('navigator.clipboard.writeText = text => (window.copied = text, Promise.resolve())');

    $page->click('[data-slot="code-block"] [aria-label="Copy"] >> nth=0')
        ->assertScript("window.copied?.includes('class Counter extends Component') === true")
        ->assertSeeIn('[data-code-block-status] >> nth=0', 'Copied');
});

it('collapses a trace step', function () {
    visit('/ai')
        ->assertScript("document.querySelector('[data-slot=\"trace-collapsible-content\"]').hidden", false)
        ->click('[data-slot="trace-collapsible-trigger"] >> nth=0')
        ->assertScript("document.querySelector('[data-slot=\"trace-collapsible-content\"]').hidden")
        ->assertAttribute('[data-slot="trace-collapsible-trigger"] >> nth=0', 'aria-expanded', 'false');
});
