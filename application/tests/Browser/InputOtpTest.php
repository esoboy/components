<?php

/*
 * Input OTP on the Controls page: one real input mirrored into the slots.
 */

it('mirrors what is typed into its slots and marks the active one', function () {
    visit('/controls')
        ->type('#code', '123')
        ->assertScript("[...document.querySelectorAll('[data-slot=\"input-otp-slot\"]')].map(slot => slot.textContent.trim()).join('')", '123')
        ->assertAttribute('[data-slot="input-otp-slot"] >> nth=3', 'data-active', 'true')
        ->assertNoJavaScriptErrors();
});

it('takes no more than its length', function () {
    visit('/controls')
        ->type('#code', '12345678')
        ->assertValue('#code', '123456');
});

it('binds the code and validates it inside a field', function () {
    visit('/controls')
        ->type('#code', '123')
        ->click('Verify')
        ->assertSee('The code field must be 6 digits.')
        ->fill('#code', '123456')
        ->click('Verify')
        ->assertDontSee('The code field must be 6 digits.');
});
