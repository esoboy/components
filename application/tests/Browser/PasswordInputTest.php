<?php

/*
 * Password Input on the Inputs page: a button shows and hides what was typed.
 */

it('shows and hides the password', function () {
    visit('/inputs')
        ->type('#password', 'secret')
        ->assertSeeIn('#password-length', '6')
        ->assertAttribute('#password', 'type', 'password')
        ->click('[data-password-toggle]')
        ->assertAttribute('#password', 'type', 'text')
        ->assertAttribute('[data-password-toggle]', 'aria-pressed', 'true')
        ->click('[data-password-toggle]')
        ->assertAttribute('#password', 'type', 'password')
        ->assertValue('#password', 'secret')
        ->assertNoJavaScriptErrors();
});

it('keeps the visibility control within the input group', function () {
    visit('/inputs')
        ->assertScript(<<<'JS'
            () => {
                const group = document.querySelector('#password').closest('[data-slot="input-group"]');
                const addon = group.querySelector('[data-slot="input-group-addon"]');
                const groupBounds = group.getBoundingClientRect();
                const addonBounds = addon.getBoundingClientRect();

                return addonBounds.top >= groupBounds.top && addonBounds.bottom <= groupBounds.bottom;
            }
            JS)
        ->assertNoJavaScriptErrors();
});
