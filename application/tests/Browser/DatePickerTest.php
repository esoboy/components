<?php

/*
 * Date Picker on the Controls page: a Calendar in a popover, with presets and times,
 * bound with wire:model on the picker.
 */

it('shows the bound date on its trigger', function () {
    visit('/controls')
        ->assertSeeIn('#demo-picker [data-slot="date-picker-value"]', 'October 26, 2026')
        ->assertNoJavaScriptErrors();
});

it('picks a date in its popover, which stays open as in shadcn', function () {
    visit('/controls')
        ->click('#demo-picker [data-date-picker-trigger]')
        ->assertScript(opened('#demo-picker [data-date-picker-content]'))
        ->click('#demo-picker button[data-day="2026-10-12"]')
        ->assertSeeIn('#date', '2026-10-12')
        ->assertSeeIn('#demo-picker [data-slot="date-picker-value"]', 'October 12, 2026')
        ->assertScript(opened('#demo-picker [data-date-picker-content]'))
        ->keys('#demo-picker button[data-day="2026-10-12"]', 'Escape')
        ->assertScript(opened('#demo-picker [data-date-picker-content]'), false);
});

it('fills a range from a preset', function () {
    $today = now()->toDateString();

    visit('/controls')
        ->click('#demo-presets [data-date-picker-trigger]')
        ->click('#demo-presets [data-date-picker-preset]:has-text("Today")')
        ->assertSeeIn('#period', "{$today} → {$today}");
});

it('binds the date with its time', function () {
    visit('/controls')
        ->click('#demo-datetime [data-date-picker-trigger]')
        ->fill('#demo-datetime [data-date-picker-time="from"]', '14:45')
        ->click('#demo-datetime button[data-day="2026-10-30"]')
        ->assertSeeIn('#starts-at', '2026-10-30 14:45');
});

it('shows a validation error inside a field', function () {
    visit('/controls')
        ->click('#birthday ~ * button[type="submit"], form[wire\\:submit="saveBirthday"] button[type="submit"]')
        ->assertSee('The birthday field is required.');
});
