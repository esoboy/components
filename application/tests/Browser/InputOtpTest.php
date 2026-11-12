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

it('starts updating the slots before continuous typing stops', function () {
    $page = visit('/controls');

    $page->script(<<<'JS'
        window.otpUpdatedDuringTyping = false;
        const input = document.querySelector('#code');
        const otp = input.closest('[data-slot="input-otp"]');
        const observer = new MutationObserver(() => {
            if (input.value.length < input.maxLength && otp.querySelector('[data-slot="input-otp-slot"]').textContent === '1') {
                window.otpUpdatedDuringTyping = true;
                observer.disconnect();
            }
        });
        observer.observe(otp, { childList: true, characterData: true, subtree: true });
        JS);

    $page->typeSlowly('#code', '123456', 55);
    $page->assertScript('window.otpUpdatedDuringTyping === true');

    $slots = "[...document.querySelectorAll('[data-slot=\"input-otp-slot\"]')].map(slot => slot.textContent.trim()).join('')";
    $page->page()->waitForFunction("$slots === '123456'");

    $page->page()->locator('#code')->press('Backspace');
    $page->page()->waitForFunction("$slots === '12345'");
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
