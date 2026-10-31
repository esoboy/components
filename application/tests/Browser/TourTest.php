<?php

/*
 * Tour on the Overlays page: steps pointing at elements, started from a button or the
 * server, reporting tour-close back to the component.
 */

const TOUR = '#overlays-tour [data-slot="tour-content"]';

it('starts from a button at its first step, beside the target', function () {
    visit('/overlays')
        ->click('Start tour')
        ->assertScript(opened(TOUR))
        ->assertScript(opened('#overlays-tour [data-slot="tour-spotlight"]'))
        ->assertSeeIn(TOUR.' [data-slot="tour-title"]', 'Dialogs')
        ->assertSeeIn(TOUR.' [data-slot="tour-counter"]', '1 / 4')
        ->assertScript("(() => { const panel = document.querySelector('".TOUR."').getBoundingClientRect(), target = document.getElementById('tour-dialog').getBoundingClientRect(); return panel.top >= target.bottom })()")
        ->assertNoJavaScriptErrors();
});

it('moves between steps and finishes', function () {
    visit('/overlays')
        ->click('Start tour')
        ->click(TOUR.' [data-tour-action="next"]')
        ->assertSeeIn(TOUR.' [data-slot="tour-title"]', 'Menus')
        ->click(TOUR.' [data-tour-action="previous"]')
        ->assertSeeIn(TOUR.' [data-slot="tour-title"]', 'Dialogs')
        ->click(TOUR.' [data-tour-action="next"]')
        ->click(TOUR.' [data-tour-action="next"]')
        ->click(TOUR.' [data-tour-action="next"]')
        ->assertSeeIn(TOUR.' [data-slot="tour-title"]', "That's all")
        ->click(TOUR.' [data-tour-action="finish"]')
        ->assertScript(opened(TOUR), false)
        ->assertSee('Tour finished');
});

it('reports a tour closed before its end', function () {
    visit('/overlays')
        ->click('Start tour')
        ->keys(TOUR, 'Escape')
        ->assertScript(opened(TOUR), false)
        ->assertSee('Tour skipped');
});

it('starts from the server at the given step', function () {
    visit('/overlays')
        ->click('Start at step 2 from server')
        ->assertScript(opened(TOUR))
        ->assertSeeIn(TOUR.' [data-slot="tour-title"]', 'Menus')
        ->assertSeeIn(TOUR.' [data-slot="tour-counter"]', '2 / 4');
});
