<?php

/*
 * Menubar on the Overlays page: one menu per trigger, and the arrow keys move along the bar.
 */

it('opens a menu under its trigger', function () {
    visit('/overlays')
        ->click('[data-slot="menubar-trigger"]:first-child')
        ->assertScript("document.querySelector('[data-slot=\"menubar-content\"]:popover-open') !== null")
        ->assertSeeIn('[data-slot="menubar-content"]:popover-open', 'New Tab')
        ->assertNoJavaScriptErrors();
});

it('moves to the next menu with the arrow keys', function () {
    visit('/overlays')
        ->click('[data-slot="menubar-trigger"]:first-child')
        ->keys('[data-slot="menubar-content"]:popover-open', 'ArrowRight')
        ->assertSeeIn('[data-slot="menubar-content"]:popover-open', 'Always Show Bookmarks Bar')
        ->keys('*:focus', 'ArrowLeft')
        ->assertSeeIn('[data-slot="menubar-content"]:popover-open', 'New Tab');
});

it('binds its checkbox and radio items, staying open', function () {
    // The Context Menu binds the same properties, so its items show what reached the server.
    visit('/overlays')
        ->click('View')
        ->click('Always Show Bookmarks Bar')
        ->assertScript("document.querySelector('[data-slot=\"menubar-content\"]:popover-open') !== null")
        ->assertScript("document.querySelector('[data-slot=\"context-menu-content\"] [role=\"menuitemcheckbox\"]').checked", false)
        ->click('Profiles')
        ->click('Colm')
        ->assertScript("document.querySelector('[data-slot=\"context-menu-content\"] [role=\"menuitemradio\"][value=\"colm\"]').checked");
});

it('switches menus on hover while one is open, and a click keeps the new one', function () {
    visit('/overlays')
        ->click('File')
        ->hover('[data-slot="menubar-trigger"]:has-text("View")')
        ->assertSeeIn('[data-slot="menubar-content"]:popover-open', 'Always Show Bookmarks Bar')
        ->click('[data-slot="menubar-trigger"]:has-text("View")')
        ->assertSeeIn('[data-slot="menubar-content"]:popover-open', 'Always Show Bookmarks Bar')
        ->assertScript("document.querySelectorAll('[data-slot=\"menubar-content\"]:popover-open').length", 1);
});
