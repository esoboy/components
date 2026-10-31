<?php

/*
 * Verification Code Button on the Inputs page: counts down once the server calls
 * Ui::countdown(), and stays usable while validation fails.
 */

it('stays usable when the server refuses to send', function () {
    visit('/inputs')
        ->click('#send-code')
        ->assertSee('The phone field is required.')
        ->assertSeeIn('#sent', '0')
        ->assertEnabled('#send-code')
        ->assertSeeIn('#send-code', 'Send code')
        ->assertNoJavaScriptErrors();
});

it('counts down after the server sends the code, then offers to resend', function () {
    visit('/inputs')
        ->fill('#phone', '13800138000')
        ->click('#send-code')
        ->assertSeeIn('#sent', '1')
        ->assertSee('Code sent')
        ->assertDisabled('#send-code')
        ->assertSeeIn('#send-code', 'Resend in')
        ->wait(6)
        ->assertEnabled('#send-code')
        ->assertSeeIn('#send-code', 'Resend code');
});
