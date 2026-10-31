<?php

/*
 * Sidebar in the demo layout: the trigger and Ctrl+B collapse it, the cookie keeps the
 * state for the server's next render, and below md it opens as a sheet.
 */

const SIDEBAR = '[data-slot="sidebar"][data-state]';

it('collapses from its trigger and remembers it for the next page', function () {
    $page = visit('/navigation')
        ->assertAttribute(SIDEBAR, 'data-state', 'expanded')
        ->click('[data-sidebar="trigger"]')
        ->assertAttribute(SIDEBAR, 'data-state', 'collapsed')
        ->assertScript("document.cookie.includes('sidebar_state=false')")
        ->assertNoJavaScriptErrors();

    $page->navigate('/navigation')
        ->assertAttribute(SIDEBAR, 'data-state', 'collapsed');
});

it('toggles with Ctrl+B', function () {
    visit('/navigation')
        ->keys('[data-sidebar="trigger"]', 'Control+b')
        ->assertAttribute(SIDEBAR, 'data-state', 'collapsed')
        ->keys('[data-sidebar="trigger"]', 'Control+b')
        ->assertAttribute(SIDEBAR, 'data-state', 'expanded');
});

it('opens as a sheet on small screens and closes on Escape', function () {
    visit('/navigation')
        ->resize(375, 812)
        ->assertScript(opened('[data-slot="sidebar-container"]'), false)
        ->click('[data-sidebar="trigger"]')
        ->assertScript(opened('[data-slot="sidebar-container"]'))
        ->keys('[data-slot="sidebar-container"] a >> nth=0', 'Escape')
        ->assertScript(opened('[data-slot="sidebar-container"]'), false);
});

it('marks the current page and navigates with wire:navigate', function () {
    visit('/navigation')
        ->assertPresent('[data-slot="sidebar-menu-button"][href="/navigation"][data-active]')
        ->click('[data-slot="sidebar-menu-button"][href="/layout"]')
        ->assertPathIs('/layout')
        ->assertPresent('[data-slot="sidebar-menu-button"][href="/layout"][data-active]');
});
