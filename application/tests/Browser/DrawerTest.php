<?php

/*
 * Drawer on the Overlays page: a modal dialog sliding from its swipe direction.
 */

it('opens from the bottom with its swipe handle and closes', function () {
    visit('/overlays')
        ->click('Open Drawer')
        ->assertScript(opened('#goal'))
        ->assertPresent('#goal [data-slot="drawer-swipe-handle"]')
        ->assertScript("(() => { const rect = document.querySelector('#goal [data-slot=\"drawer-content\"]').getBoundingClientRect(); return rect.bottom >= innerHeight - 1 })()")
        ->click('#goal [data-slot="drawer-footer"] button[command="close"]')
        ->assertScript(opened('#goal'), false)
        ->assertNoJavaScriptErrors();
});

it('opens from the right side', function () {
    visit('/overlays')
        ->click('Right Drawer')
        ->assertScript(opened('#side'))
        ->assertAttribute('#side', 'data-swipe-direction', 'right')
        ->assertScript("(() => { const rect = document.querySelector('#side [data-slot=\"drawer-content\"]').getBoundingClientRect(); return rect.right >= innerWidth - 1 })()")
        ->keys('#side', 'Escape')
        ->assertScript(opened('#side'), false);
});
