<?php

/*
 * Toasts on the Overlays page: shown by Ui::toast() and $dispatch('ui-toast'), with an
 * action calling back the component, replaced by id, and kept across wire:navigate.
 */

const TOAST = '[data-slot="toast-viewport"] > [data-slot="toast"]';

it('shows a toast dispatched from the browser', function () {
    visit('/overlays')
        ->click('Default')
        ->assertScript(opened('[data-slot="toast-viewport"]'))
        ->assertSeeIn(TOAST, 'Event has been created')
        ->assertSeeIn(TOAST, 'Sunday, December 03, 2023 at 9:00 AM')
        ->assertNoJavaScriptErrors();
});

it('marks a toast with its type', function () {
    visit('/overlays')
        ->click('Success')
        ->assertAttribute(TOAST, 'data-type', 'success')
        ->assertSeeIn(TOAST, 'Success toast')
        ->assertPresent(TOAST.' [data-slot="toast-icon"] svg');
});

it('shows a toast from the server whose action calls the component', function () {
    visit('/overlays')
        ->click('button:has-text("Delete (with Undo)")')
        ->assertSeeIn('#deleted', '1')
        ->assertSeeIn(TOAST, 'Deleted')
        ->click(TOAST.' [data-toast-action]')
        ->assertSeeIn('#deleted', '0')
        ->assertSee('Restored');
});

it('replaces a toast with the same id', function () {
    visit('/overlays')
        ->click('button:has-text("Create event (loading)")')
        ->assertSeeIn(TOAST, 'Event created')
        ->assertCount(TOAST.'[data-id="event"]', 1)
        ->assertAttribute(TOAST.'[data-id="event"]', 'data-type', 'success');
});

it('closes a toast from its close button', function () {
    visit('/overlays')
        ->click('Info')
        ->assertSeeIn(TOAST, 'Info toast')
        ->hover(TOAST)
        ->click(TOAST.' [data-toast-close]')
        ->assertCount(TOAST.':not([data-ending-style])', 0);
});

it('keeps a toast shown before navigating to another page', function () {
    visit('/overlays')
        ->click('Save and go to General')
        ->assertPathIs('/general')
        ->assertSeeIn(TOAST, 'Back on the General page.');
});
