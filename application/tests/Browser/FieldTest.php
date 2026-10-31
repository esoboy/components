<?php

/*
 * Field on the Forms page: Livewire's validation errors show under each control inside
 * a Field or Field Set, and never on a standalone control.
 */

it('shows each control its error on submit', function () {
    visit('/forms')
        ->click('Save')
        ->assertSee('The email field is required.')
        ->assertSee('The bio field is required.')
        ->assertSee('The role field is required.')
        ->assertSee('The plan field is required.')
        ->assertSee('The channels field is required.')
        ->assertSee('The terms field must be accepted.')
        ->assertAttribute('#email', 'aria-invalid', 'true')
        ->assertScript("document.getElementById(document.getElementById('email').getAttribute('aria-describedby').split(' ').at(-1))?.textContent.includes('The email field is required.') === true")
        ->assertNoJavaScriptErrors();
});

it('validates as soon as the user leaves a live.blur field', function () {
    visit('/forms')
        ->type('#email', 'not-an-email')
        ->click('#bio')
        ->assertSee('The email field must be a valid email address.')
        ->clear('#email')
        ->type('#email', 'ada@example.com')
        ->click('#bio')
        ->assertDontSee('The email field must be a valid email address.')
        ->assertAttributeMissing('#email', 'aria-invalid');
});

it('never shows errors on a control outside a field', function () {
    visit('/forms')
        ->click('Save')
        ->assertSee('The email field is required.')
        ->assertAttributeMissing('#email-standalone', 'aria-invalid');
});

it('saves once every control is valid', function () {
    visit('/forms')
        ->fill('#email', 'ada@example.com')
        ->fill('#bio', 'Mathematician and writer.')
        ->select('#role', 'admin')
        ->click('label[for="plan-pro"]')
        ->click('label[for="channel-sms"]')
        ->click('label[for="terms"]')
        ->click('Save')
        ->assertSee('Saved at')
        ->assertDontSee('is required');
});

it('binds a switch', function () {
    visit('/forms')
        ->assertChecked('#notifications')
        ->click('label[for="notifications"]')
        ->assertNotChecked('#notifications');
});

it('counts characters against a soft limit', function () {
    visit('/forms')
        ->type('#bio', 'Hello')
        ->assertSeeIn('[data-slot="input-group"]:has(#bio) [data-slot="input-group-count"]', '5/160');
});
