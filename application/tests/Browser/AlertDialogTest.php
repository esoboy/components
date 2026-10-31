<?php

/*
 * Alert Dialog on the Overlays page: a modal alertdialog whose action runs the
 * server's method and closes, and whose cancel only closes.
 */

it('opens as a modal alertdialog', function () {
    visit('/overlays')
        ->click('Show alert dialog')
        ->assertScript(opened('#delete-account'))
        ->assertAttribute('#delete-account', 'role', 'alertdialog')
        ->assertScript("document.getElementById('delete-account').getAttribute('aria-labelledby') === document.querySelector('#delete-account [data-slot=\"alert-dialog-title\"]').id")
        ->assertNoJavaScriptErrors();
});

it('closes without acting on cancel', function () {
    visit('/overlays')
        ->click('Show alert dialog')
        ->click('#delete-account [data-slot="alert-dialog-footer"] button:first-child')
        ->assertScript(opened('#delete-account'), false)
        ->assertSeeIn('#deleted', '0');
});

it('acts and closes on its action', function () {
    visit('/overlays')
        ->click('Delete chat')
        ->assertScript(opened('#delete-chat'))
        ->click('#delete-chat [data-slot="alert-dialog-footer"] button:last-child')
        ->assertScript(opened('#delete-chat'), false)
        ->assertSeeIn('#deleted', '1');
});
