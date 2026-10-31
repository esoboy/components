<?php

/*
 * <x-ui::confirm> in the layout answers wire:confirm instead of the browser's confirm():
 * an alert dialog, a destructive one, or a popover at the element.
 */

it('asks in an alert dialog with the question and the confirm label', function () {
    visit('/overlays')
        ->click('button[wire\\:confirm]')
        ->assertScript(opened('#ui-confirm'))
        ->assertSeeIn('#ui-confirm', 'Publish this post?')
        ->assertSeeIn('#ui-confirm button[value="confirm"]', 'Publish')
        ->assertSeeIn('#deleted', '0');
});

it('runs the action only once confirmed', function () {
    $page = visit('/overlays');

    $page->click('button[wire\\:confirm]')
        ->click('#ui-confirm button:not([value="confirm"])')
        ->assertScript(opened('#ui-confirm'), false)
        ->assertSeeIn('#deleted', '0');

    $page->click('button[wire\\:confirm]')
        ->click('#ui-confirm button[value="confirm"]')
        ->assertScript(opened('#ui-confirm'), false)
        ->assertSeeIn('#deleted', '1');
});

it('asks destructively with a red confirm button', function () {
    visit('/overlays')
        ->click('button[wire\\:confirm\\.destructive]')
        ->assertScript(opened('#ui-confirm-destructive'))
        ->assertSeeIn('#ui-confirm-destructive', 'Delete this item?')
        ->assertAttribute('#ui-confirm-destructive button[value="confirm"]', 'data-variant', 'destructive')
        ->click('#ui-confirm-destructive button[value="confirm"]')
        ->assertSeeIn('#deleted', '1');
});

it('asks in a popover beside the element', function () {
    visit('/overlays')
        ->click('button[wire\\:confirm\\.popover]')
        ->assertScript(opened('#ui-confirm-popover'))
        ->assertSeeIn('#ui-confirm-popover', 'Archive this item?')
        ->assertScript("(() => { const popover = document.getElementById('ui-confirm-popover').getBoundingClientRect(), button = document.querySelector('button[wire\\\\:confirm\\\\.popover]').getBoundingClientRect(); return popover.bottom <= button.top + 1 })()")
        ->click('#ui-confirm-popover button[value="confirm"]')
        ->assertScript(opened('#ui-confirm-popover'), false)
        ->assertSeeIn('#deleted', '1');
});
