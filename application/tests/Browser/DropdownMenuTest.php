<?php

/*
 * Dropdown Menu on the Overlays page: a menu popover with arrow keys, a submenu, and
 * checkbox, radio and action items bound to the component.
 */

const MENU = '[data-slot="dropdown-menu-content"]';

it('opens under its trigger and takes focus', function () {
    visit('/overlays')
        ->click('#tour-dropdown')
        ->assertScript(opened(MENU))
        ->assertScript("document.activeElement.matches('".MENU."')")
        ->assertAttribute('#tour-dropdown', 'aria-expanded', 'true')
        ->assertNoJavaScriptErrors();
});

it('moves through its enabled items with the arrow keys', function () {
    visit('/overlays')
        ->click('#tour-dropdown')
        ->keys(MENU, 'ArrowDown')
        ->assertScript("document.activeElement.textContent.trim().startsWith('Profile')")
        ->keys('*:focus', 'ArrowDown')
        ->assertScript('document.activeElement.textContent.trim()', 'Billing')
        ->keys('*:focus', 'ArrowDown')
        ->assertScript('document.activeElement.textContent.trim()', 'Invite users')
        ->keys('*:focus', 'End')
        ->assertScript('document.activeElement.textContent.trim()', 'Delete')
        ->keys('*:focus', 'Home')
        ->assertScript("document.activeElement.textContent.trim().startsWith('Profile')");
});

it('closes on Escape and gives focus back to its trigger', function () {
    visit('/overlays')
        ->click('#tour-dropdown')
        ->keys(MENU, 'Escape')
        ->assertScript(opened(MENU), false)
        ->assertScript('document.activeElement.id', 'tour-dropdown');
});

it('opens its submenu beside it from the keyboard', function () {
    visit('/overlays')
        ->click('#tour-dropdown')
        ->keys(MENU.' [data-slot="dropdown-menu-sub-trigger"]', 'ArrowRight')
        ->assertScript(opened('[data-slot="dropdown-menu-sub-content"]'))
        ->assertScript("(() => { const sub = document.querySelector('[data-slot=\"dropdown-menu-sub-content\"]').getBoundingClientRect(), menu = document.querySelector('".MENU."').getBoundingClientRect(); return sub.left >= menu.right - 8 })()")
        ->keys('*:focus', 'ArrowLeft')
        ->assertScript(opened('[data-slot="dropdown-menu-sub-content"]'), false)
        ->assertScript('document.activeElement.textContent.trim()', 'Invite users');
});

it('binds checkbox and radio items, staying open as Base UI does', function () {
    visit('/overlays')
        ->assertSeeIn('#status-bar', 'on')
        ->click('#tour-dropdown')
        ->click('Status Bar')
        ->assertSeeIn('#status-bar', 'off')
        ->assertScript(opened(MENU))
        ->assertScript("document.querySelector('".MENU." [role=\"menuitemcheckbox\"]').checked", false)
        ->click(MENU.' [data-slot="dropdown-menu-radio-item"]:has-text("Top")')
        ->assertSeeIn('#position', 'top')
        ->assertScript(opened(MENU));
});

it('runs an item action and closes', function () {
    visit('/overlays')
        ->click('#tour-dropdown')
        ->click(MENU.' [data-variant="destructive"]')
        ->assertScript(opened(MENU), false)
        ->assertSeeIn('#deleted', '1');
});

it('keeps a submenu opened by hover open when its trigger is clicked', function () {
    visit('/overlays')
        ->click('#tour-dropdown')
        ->hover(MENU.' [data-slot="dropdown-menu-sub-trigger"]')
        ->assertScript(opened(MENU.' [data-slot="dropdown-menu-sub-content"]'))
        ->click(MENU.' [data-slot="dropdown-menu-sub-trigger"]')
        ->assertScript(opened(MENU.' [data-slot="dropdown-menu-sub-content"]'))
        ->click(MENU.' [data-slot="dropdown-menu-sub-trigger"]')
        ->assertScript(opened(MENU.' [data-slot="dropdown-menu-sub-content"]'), false);
});
