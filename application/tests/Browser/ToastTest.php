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

it('stacks multiple toasts and spreads them out on hover', function () {
    $page = visit('/overlays');
    $page->script(<<<'JS'
        function () {
            const notifications = [
                { message: 'Event created', description: 'Sunday, December 03, 2023 at 9:00 AM' },
                { message: 'Success toast', type: 'success' },
                { message: 'Info toast', type: 'info' },
            ];
            for (const notification of notifications) {
                window.dispatchEvent(new CustomEvent('ui-toast', { detail: { ...notification, duration: 0 } }));
            }
        }
        JS);

    $isStacked = <<<'JS'
        function () {
            const [oldest, middle, newest] = document.querySelectorAll('[data-slot="toast"]');
            const [back, center, front] = [oldest, middle, newest].map(toast => toast.getBoundingClientRect());

            return back.width < center.width && center.width < front.width
                && back.top < center.top && center.top < front.top && center.bottom > front.top;
        }
        JS;

    $page->assertSeeIn(TOAST.'[data-type="info"]', 'Info toast')
        ->wait(0.55)
        ->assertScript($isStacked)
        ->hover(TOAST.'[data-type="info"]')
        ->wait(0.55)
        ->assertScript(<<<'JS'
            function () {
                const toasts = [...document.querySelectorAll('[data-slot="toast"]')];
                const [oldest, middle, newest] = toasts.map(toast => toast.getBoundingClientRect());

                return oldest.bottom < middle.top && middle.bottom < newest.top
                    && oldest.width === newest.width && oldest.height > newest.height
                    && toasts.every(toast => getComputedStyle(toast.firstElementChild).opacity === '1');
            }
            JS)
        ->hover('#tour-toast')
        ->wait(0.55)
        ->assertScript($isStacked)
        ->assertNoJavaScriptErrors();
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
