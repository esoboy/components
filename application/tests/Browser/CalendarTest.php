<?php

/*
 * Calendar on the Controls page: a month grid bound with wire:model, single or a range.
 */

it('shows the bound month with the bound day selected', function () {
    visit('/controls')
        ->assertAttribute('#demo-calendar button[data-day="2026-10-26"]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});

it('binds the day picked', function () {
    visit('/controls')
        ->click('#demo-calendar button[data-day="2026-10-15"]')
        ->assertSeeIn('#date', '2026-10-15')
        ->assertAttribute('#demo-calendar button[data-day="2026-10-15"]', 'aria-pressed', 'true');
});

it('moves the focused day with the arrow keys and picks it with Enter', function () {
    visit('/controls')
        ->keys('#demo-calendar button[data-day="2026-10-26"]', 'ArrowRight')
        ->keys('*:focus', 'ArrowDown')
        ->keys('*:focus', 'Enter')
        ->assertSeeIn('#date', '2026-11-03');
});

it('changes month from its buttons and its dropdowns', function () {
    visit('/controls')
        ->click('#demo-calendar [data-calendar-next]')
        ->assertPresent('#demo-calendar button[data-day="2026-11-15"]')
        ->select('#demo-calendar [data-calendar-year]', '2030')
        ->assertPresent('#demo-calendar button[data-day="2030-11-15"]');
});

it('picks a range from two days', function () {
    visit('/controls')
        ->click('#demo-range button[data-day="2026-10-05"]')
        ->click('#demo-range button[data-day="2026-10-09"]')
        ->assertSeeIn('#range', '2026-10-05 → 2026-10-09');
});
