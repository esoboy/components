<?php

/*
 * Context Menu on the Overlays page: a menu opened at the pointer by a right click.
 */

const CONTEXT_MENU = '[data-slot="context-menu-content"]';

it('opens at the pointer on right click', function () {
    $page = visit('/overlays');

    $page->rightClick('Right click here')
        ->assertScript(opened(CONTEXT_MENU))
        ->assertScript("(() => { const menu = document.querySelector('".CONTEXT_MENU."').getBoundingClientRect(), area = document.querySelector('[data-slot=\"context-menu-trigger\"]').getBoundingClientRect(); return menu.left >= area.left && menu.left <= area.right && menu.top >= area.top && menu.top <= area.bottom })()")
        ->assertScript("document.activeElement.matches('".CONTEXT_MENU."')")
        ->assertNoJavaScriptErrors();
});

it('skips its disabled item and closes on Escape', function () {
    visit('/overlays')
        ->rightClick('Right click here')
        ->keys(CONTEXT_MENU, 'ArrowDown')
        ->assertScript("document.activeElement.textContent.trim().startsWith('Back')")
        ->keys('*:focus', 'ArrowDown')
        ->assertScript("document.activeElement.textContent.trim().startsWith('Reload')")
        ->keys('*:focus', 'Escape')
        ->assertScript(opened(CONTEXT_MENU), false);
});

it('binds its checkbox and radio items, staying open', function () {
    // The Menubar binds the same properties, so its items show what reached the server.
    visit('/overlays')
        ->rightClick('Right click here')
        ->click('Show Bookmarks')
        ->assertScript(opened(CONTEXT_MENU))
        ->assertScript("document.querySelector('[data-slot=\"menubar-content\"] [role=\"menuitemcheckbox\"]').checked", false)
        ->click('Colm Tuite')
        ->assertScript("document.querySelector('[data-slot=\"menubar-content\"] [role=\"menuitemradio\"][value=\"colm\"]').checked");
});
