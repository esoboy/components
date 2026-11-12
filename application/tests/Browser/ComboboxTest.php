<?php

/*
 * Combobox on the Controls page: type to filter, arrows and Enter to choose, chips for
 * several values, and autocomplete with suggestions from the server.
 */

const COMBOBOX_CONTENT = 'eso-combobox:has(#demo-combobox) [data-slot="combobox-content"]';

it('dismisses the list when clicking outside', function () {
    visit('/controls')
        ->click('#demo-combobox')
        ->assertScript(opened(COMBOBOX_CONTENT))
        ->click('#demo-combobox-label')
        ->assertScript(opened(COMBOBOX_CONTENT), false)
        ->assertAttribute('#demo-combobox', 'aria-expanded', 'false');
});

it('toggles the list from its trigger', function () {
    $trigger = 'eso-combobox:has(#demo-combobox) [data-combobox-trigger]';

    visit('/controls')
        ->click($trigger)
        ->assertScript(opened(COMBOBOX_CONTENT))
        ->assertAttribute($trigger, 'aria-expanded', 'true')
        ->click($trigger)
        ->assertScript(opened(COMBOBOX_CONTENT), false)
        ->assertAttribute($trigger, 'aria-expanded', 'false');
});

it('keeps the list closed after Escape while filtering is in flight', function () {
    $page = visit('/controls')->click('#demo-combobox')->assertScript(opened(COMBOBOX_CONTENT));
    $page->script(<<<'JS'
        (() => {
            const fetch = window.fetch;
            const hold = new Promise(resolve => {
                window.releaseComboboxResponse = () => {
                    window.fetch = fetch;
                    resolve();
                };
            });
            window.fetch = async (...args) => {
                window.comboboxRequestStarted = true;
                const response = await fetch(...args);
                await hold;
                return response;
            };
        })()
        JS);

    $page->type('#demo-combobox', 'nu');
    $page->page()->waitForFunction('window.comboboxRequestStarted === true');
    $page->keys('#demo-combobox', 'Escape')
        ->assertScript(opened(COMBOBOX_CONTENT), false);
    $page->script('window.releaseComboboxResponse()');

    $page->assertScript("document.querySelectorAll('eso-combobox:has(#demo-combobox) [data-slot=\"combobox-item\"]').length", 1)
        ->assertScript(opened(COMBOBOX_CONTENT), false)
        ->click('#demo-combobox')
        ->assertScript(opened(COMBOBOX_CONTENT));
});

it('filters its items as you type and chooses one with the keyboard', function () {
    visit('/controls')
        ->type('#demo-combobox', 'nu')
        ->assertScript(opened('eso-combobox:has(#demo-combobox) [data-slot="combobox-content"]'))
        ->assertScript("[...document.querySelectorAll('eso-combobox:has(#demo-combobox) [data-slot=\"combobox-item\"]')].filter(item => ! item.hidden).map(item => item.dataset.value).join()", 'nuxt')
        ->keys('#demo-combobox', ['ArrowDown', 'Enter'])
        ->assertSeeIn('#framework', 'nuxt')
        ->assertValue('#demo-combobox', 'Nuxt.js')
        ->assertScript(opened(COMBOBOX_CONTENT), false)
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
        ->assertScript(opened(COMBOBOX_CONTENT), false)
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
        ->assertSeeIn('#email', 'ada@outlook.com')
        ->assertScript(opened('eso-combobox:has(#demo-email) [data-slot="combobox-content"]'), false);
});
