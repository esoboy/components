<?php

/*
 * Emoji Picker on the Inputs page: emojis load from the package's data when it shows,
 * search filters them, and picking one dispatches emoji-select.
 */

const EMOJI = '#mood-picker [data-slot="emoji-picker-emoji"]';

it('loads its emojis once it shows and dispatches the one picked', function () {
    $page = visit('/inputs')->assertCount(EMOJI, 0);

    $page->script("document.getElementById('mood-picker').scrollIntoView()");

    $page->assertScript("document.querySelectorAll('".EMOJI."').length > 100")
        ->click(EMOJI.' >> nth=0')
        ->assertScript("document.getElementById('mood').textContent.trim() === document.querySelector('".EMOJI."').textContent")
        ->assertNoJavaScriptErrors();
});

it('filters by search', function () {
    $page = visit('/inputs');

    $page->script("document.getElementById('mood-picker').scrollIntoView()");

    $page->assertScript("document.querySelectorAll('".EMOJI."').length > 100")
        ->type('#mood-picker [data-slot="emoji-picker-search"]', 'pizza')
        ->assertScript("[...document.querySelectorAll('".EMOJI."')].filter(emoji => ! emoji.hidden).map(emoji => emoji.textContent)[0]", '🍕')
        ->assertScript("[...document.querySelectorAll('".EMOJI."')].filter(emoji => ! emoji.hidden).length < 5");
});

it('works inside a popover', function () {
    visit('/inputs')
        ->click('#reaction-trigger')
        ->assertScript("document.querySelectorAll('[data-slot=\"popover-content\"]:popover-open [data-slot=\"emoji-picker-emoji\"]').length > 100")
        ->click('[data-slot="popover-content"]:popover-open [data-slot="emoji-picker-emoji"] >> nth=0')
        ->assertScript("document.getElementById('reaction-trigger').textContent.trim() !== 'Open emoji picker'");
});
