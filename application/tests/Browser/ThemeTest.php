<?php

/*
 * The theme on the General page and the layout's header: any control with
 * data-theme-value picks it, the cookie keeps it, and the server renders it.
 */

it('switches to dark from a button and keeps it for the next page', function () {
    $page = visit('/general')
        ->assertScript("document.documentElement.dataset.theme !== 'dark'")
        ->click('[data-theme-value="toggle"][aria-label="Toggle theme"]')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertScript("getComputedStyle(document.documentElement).colorScheme.includes('dark')")
        ->assertNoJavaScriptErrors();

    $page->navigate('/forms')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertChecked('[data-theme-value="dark"]');
});

it('picks a theme from the header toggle group and every control follows', function () {
    visit('/general')
        ->click('label:has(> [data-theme-value="dark"][aria-label="Dark"])')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->assertChecked('input[role="switch"][data-theme-value="toggle"]')
        ->click('label:has(> [data-theme-value="light"][aria-label="Light"])')
        ->assertScript('document.documentElement.dataset.theme', 'light');
});

it('goes back to the system theme', function () {
    visit('/general')
        ->click('label:has(> [data-theme-value="dark"][aria-label="Dark"])')
        ->assertScript('document.documentElement.dataset.theme', 'dark')
        ->click('label:has(> [data-theme-value="system"][aria-label="System"])')
        ->assertScript("document.documentElement.hasAttribute('data-theme')", false);
});
