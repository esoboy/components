<?php

/*
 * Command on the Controls page: filters as you type, Enter clicks the highlighted item,
 * and Command Dialog puts it in a modal.
 */

it('filters by letters in order and by keywords', function () {
    $visible = "[...document.querySelectorAll('#demo-command [data-slot=\"command-item\"]')].filter(item => ! item.hidden).map(item => item.textContent.trim().split(/\\s+/)[0]).join()";

    visit('/controls')
        ->type('#demo-command [data-slot="command-input"]', 'clr')
        ->assertScript($visible, 'Calendar,Calculator')
        ->clear('#demo-command [data-slot="command-input"]')
        ->type('#demo-command [data-slot="command-input"]', 'preferences')
        ->assertScript($visible, 'Settings')
        ->assertNoJavaScriptErrors();
});

it('shows its empty state when nothing matches', function () {
    visit('/controls')
        ->type('#demo-command [data-slot="command-input"]', 'qqqq')
        ->assertSeeIn('#demo-command', 'No results found.');
});

it('runs the highlighted item on Enter, skipping disabled ones', function () {
    visit('/controls')
        ->type('#demo-command [data-slot="command-input"]', 'cal')
        ->keys('#demo-command [data-slot="command-input"]', ['ArrowDown', 'Enter'])
        ->assertSeeIn('#ran', 'calendar');
});

it('runs an item from the command dialog, which the server closes', function () {
    visit('/controls')
        ->click('Open command menu')
        ->assertScript(opened('#command-menu'))
        ->type('#command-menu [data-slot="command-input"]', 'emoji')
        ->keys('#command-menu [data-slot="command-input"]', 'Enter')
        ->assertSeeIn('#ran', 'emoji')
        ->assertScript(opened('#command-menu'), false);
});
