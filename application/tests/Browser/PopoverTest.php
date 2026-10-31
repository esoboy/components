<?php

/*
 * Popover on the Overlays page: a native popover anchored to its trigger, toggled by it,
 * dismissed by clicks outside, and kept open through Livewire morphs.
 */

const POPOVER = '[data-slot="popover-content"]:not([id^="ui-confirm"])';

const POPOVER_TRIGGER = 'button[command="toggle-popover"]:has-text("Open popover")';

it('opens under its trigger, aligned to its start', function () {
    visit('/overlays')
        ->click('Open popover')
        ->assertScript(opened(POPOVER))
        ->assertSeeIn(POPOVER.' [data-slot="popover-title"]', 'Dimensions')
        ->assertScript("(() => { const popover = document.querySelector('".POPOVER."').getBoundingClientRect(), trigger = document.evaluate(\"//button[normalize-space()='Open popover']\", document, null, 9).singleNodeValue.getBoundingClientRect(); return Math.abs(popover.top - trigger.bottom - 4) < 1 && Math.abs(popover.left - trigger.left) < 1 })()")
        ->assertAttribute(POPOVER_TRIGGER, 'aria-expanded', 'true')
        ->assertNoJavaScriptErrors();
});

it('closes from its trigger and from a click outside', function () {
    $page = visit('/overlays');

    $page->click('Open popover')
        ->assertScript(opened(POPOVER))
        ->click('Open popover')
        ->assertScript(opened(POPOVER), false);

    $page->click('Open popover')
        ->assertScript(opened(POPOVER))
        ->click('h2 >> nth=0')
        ->assertScript(opened(POPOVER), false);
});

it('stays open while Livewire updates the page', function () {
    $page = visit('/overlays')
        ->click('Open popover')
        ->assertScript(opened(POPOVER));

    $page->script("Livewire.first().\$set('deleted', 5)");

    $page->assertSeeIn('#deleted', '5')
        ->assertScript(opened(POPOVER))
        ->assertAttribute(POPOVER_TRIGGER, 'aria-expanded', 'true');
});
