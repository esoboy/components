<?php

/*
 * Tooltip and Hover Card on the Overlays page: manual popovers shown on hover and focus.
 */

it('shows a tooltip over its trigger on hover and hides it after', function () {
    visit('/overlays')
        ->hover('[data-tooltip-trigger]')
        ->assertScript(opened('[data-slot="tooltip-content"]'))
        ->assertSeeIn('[data-slot="tooltip-content"]', 'Add to library')
        ->assertScript("(() => { const tip = document.querySelector('[data-slot=\"tooltip-content\"]').getBoundingClientRect(), trigger = document.querySelector('[data-tooltip-trigger]').getBoundingClientRect(); return tip.bottom <= trigger.top })()")
        ->hover('h2 >> nth=0')
        ->assertScript(opened('[data-slot="tooltip-content"]'), false)
        ->assertNoJavaScriptErrors();
});

it('describes its trigger while shown', function () {
    visit('/overlays')
        ->hover('[data-tooltip-trigger]')
        ->assertScript("document.querySelector('[data-tooltip-trigger]').ariaDescribedByElements?.[0] === document.querySelector('[data-slot=\"tooltip-content\"]')");
});

it('shows a hover card after its delay', function () {
    visit('/overlays')
        ->hover('Hover Here')
        ->assertScript(opened('[data-slot="hover-card-content"]'))
        ->assertSeeIn('[data-slot="hover-card-content"]', 'The React Framework')
        ->hover('h2 >> nth=0')
        ->assertScript(opened('[data-slot="hover-card-content"]'), false);
});
