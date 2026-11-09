<?php

/*
 * Combobox on the Controls page: type to filter, arrows and Enter to choose, chips for
 * several values, and autocomplete with suggestions from the server.
 */

it('filters its items as you type and chooses one with the keyboard', function () {
    visit('/controls')
        ->type('#demo-combobox', 'nu')
        ->assertScript(opened('eso-combobox:has(#demo-combobox) [data-slot="combobox-content"]'))
        ->assertScript("[...document.querySelectorAll('eso-combobox:has(#demo-combobox) [data-slot=\"combobox-item\"]')].filter(item => ! item.hidden).map(item => item.dataset.value).join()", 'nuxt')
        ->keys('#demo-combobox', ['ArrowDown', 'Enter'])
        ->assertSeeIn('#framework', 'nuxt')
        ->assertValue('#demo-combobox', 'Nuxt.js')
        ->assertNoJavaScriptErrors();
});

it('shows its empty state when nothing matches', function () {
    visit('/controls')
        ->assertAttribute('input#demo-combobox', 'aria-labelledby', 'demo-combobox-label')
        ->type('#demo-combobox', 'zzz')
        ->assertSeeIn('eso-combobox:has(#demo-combobox) [data-slot="combobox-content"]', 'No items found.')
        ->click('Use Astro')
        ->assertSeeIn('#framework', 'astro')
        ->assertPresent('eso-combobox:has(#demo-combobox) [data-slot="combobox-footer"] button:disabled')
        ->assertAttribute('input#demo-combobox', 'aria-labelledby', 'demo-combobox-label');
});

it('chooses an item on click and clears it', function () {
    visit('/controls')
        ->click('#demo-combobox')
        ->click('eso-combobox:has(#demo-combobox) [data-slot="combobox-item"][data-value="astro"]')
        ->assertSeeIn('#framework', 'astro')
        ->click('eso-combobox:has(#demo-combobox) [data-combobox-clear]')
        ->assertSeeIn('#framework', 'none');
});

it('adds and removes chips', function () {
    visit('/controls')
        ->assertSeeIn('eso-combobox:has(#demo-chips) [data-slot="combobox-chip"]', 'Next.js')
        ->click('#demo-chips')
        ->click('eso-combobox:has(#demo-chips) [data-slot="combobox-item"][data-value="remix"]')
        ->assertSeeIn('#frameworks', 'next,remix')
        ->keys('#demo-chips', 'Backspace')
        ->assertSeeIn('#frameworks', 'next');
});

it('suggests from the server while the value stays the typed text', function () {
    visit('/controls')
        ->type('#demo-email', 'ada@o')
        ->assertSeeIn('#email', 'ada@o')
        ->assertSeeIn('eso-combobox:has(#demo-email) [data-slot="combobox-content"]', 'ada@outlook.com')
        ->click('eso-combobox:has(#demo-email) [data-slot="combobox-item"]')
        ->assertSeeIn('#email', 'ada@outlook.com');
});
