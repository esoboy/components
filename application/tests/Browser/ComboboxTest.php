<?php

/*
 * Combobox on the Controls page: type to filter, arrows and Enter to choose, chips for
 * several values, and autocomplete with suggestions from the server.
 */

it('filters its items as you type and chooses one with the keyboard', function () {
    visit('/controls')
        ->type('#demo-combobox [role="combobox"]', 'nu')
        ->assertScript(opened('#demo-combobox [data-slot="combobox-content"]'))
        ->assertScript("[...document.querySelectorAll('#demo-combobox [data-slot=\"combobox-item\"]')].filter(item => ! item.hidden).map(item => item.dataset.value).join()", 'nuxt')
        ->keys('#demo-combobox [role="combobox"]', ['ArrowDown', 'Enter'])
        ->assertSeeIn('#framework', 'nuxt')
        ->assertValue('#demo-combobox [role="combobox"]', 'Nuxt.js')
        ->assertNoJavaScriptErrors();
});

it('shows its empty state when nothing matches', function () {
    visit('/controls')
        ->type('#demo-combobox [role="combobox"]', 'zzz')
        ->assertSeeIn('#demo-combobox [data-slot="combobox-content"]', 'No items found.');
});

it('chooses an item on click and clears it', function () {
    visit('/controls')
        ->click('#demo-combobox [role="combobox"]')
        ->click('#demo-combobox [data-slot="combobox-item"][data-value="astro"]')
        ->assertSeeIn('#framework', 'astro')
        ->click('#demo-combobox [data-combobox-clear]')
        ->assertSeeIn('#framework', 'none');
});

it('adds and removes chips', function () {
    visit('/controls')
        ->assertSeeIn('#demo-chips [data-slot="combobox-chip"]', 'Next.js')
        ->click('#demo-chips [data-slot="combobox-chip-input"]')
        ->click('#demo-chips [data-slot="combobox-item"][data-value="remix"]')
        ->assertSeeIn('#frameworks', 'next,remix')
        ->keys('#demo-chips [data-slot="combobox-chip-input"]', 'Backspace')
        ->assertSeeIn('#frameworks', 'next');
});

it('suggests from the server while the value stays the typed text', function () {
    visit('/controls')
        ->type('#demo-email [role="combobox"]', 'ada@o')
        ->assertSeeIn('#email', 'ada@o')
        ->assertSeeIn('#demo-email [data-slot="combobox-content"]', 'ada@outlook.com')
        ->click('#demo-email [data-slot="combobox-item"]')
        ->assertSeeIn('#email', 'ada@outlook.com');
});
