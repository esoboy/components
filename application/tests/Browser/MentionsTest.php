<?php

/*
 * Mentions on the Inputs page: a Textarea suggesting its items after a prefix, at the
 * caret, with server-side search.
 */

it('suggests its items after the prefix and inserts the chosen one', function () {
    visit('/inputs')
        ->type('#comment', 'Hi @')
        ->assertScript(opened('#comment ~ [data-slot="mentions-content"], eso-mentions:has(#comment) [data-slot="mentions-content"]'))
        ->assertSeeIn('eso-mentions:has(#comment) [data-slot="mentions-list"]', '张三')
        ->keys('#comment', 'Enter')
        ->assertValue('#comment', 'Hi @zhangsan ')
        ->assertNoJavaScriptErrors();
});

it('filters by what follows the prefix and skips disabled items', function () {
    visit('/inputs')
        ->type('#comment', '@li')
        ->assertScript("[...document.querySelectorAll('eso-mentions:has(#comment) [data-slot=\"mentions-item\"]')].filter(item => item.checkVisibility()).map(item => item.dataset.value).join()", 'lisi')
        ->clear('#comment')
        ->type('#comment', '@wang')
        ->assertAttribute('eso-mentions:has(#comment) [data-slot="mentions-item"][data-value="wangwu"]', 'aria-disabled', 'true');
});

it('suggests the items of each prefix', function () {
    visit('/inputs')
        ->type('#comment', '#')
        ->assertScript("[...document.querySelectorAll('eso-mentions:has(#comment) [data-slot=\"mentions-item\"]')].filter(item => item.checkVisibility()).map(item => item.dataset.value).join()", 'design,release');
});

it('suggests what the server finds', function () {
    visit('/inputs')
        ->type('#reply', '@hug')
        ->assertScript("[...document.querySelectorAll('eso-mentions:has(#reply) [data-slot=\"mentions-item\"]')].filter(item => item.checkVisibility()).map(item => item.dataset.value).join()", 'HugoAlves');
});
