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
