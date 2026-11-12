<?php

/*
 * Date Picker on the Controls page: a Calendar in a popover, with presets and times,
 * bound with wire:model on the picker.
 */

it('shows the bound date on its trigger', function () {
    visit('/controls')
        ->assertSeeIn('eso-date-picker:has(#demo-picker) [data-slot="date-picker-value"]', 'October 26, 2026')
        ->assertNoJavaScriptErrors();
});

it('picks a date in its popover, which stays open as in shadcn', function () {
    visit('/controls')
        ->click('eso-date-picker:has(#demo-picker) [data-date-picker-trigger]')
        ->assertScript(opened('eso-date-picker:has(#demo-picker) [data-date-picker-content]'))
        ->click('eso-date-picker:has(#demo-picker) button[data-day="2026-10-12"]')
        ->assertSeeIn('#date', '2026-10-12')
        ->assertSeeIn('eso-date-picker:has(#demo-picker) [data-slot="date-picker-value"]', 'October 12, 2026')
        ->assertScript(opened('eso-date-picker:has(#demo-picker) [data-date-picker-content]'))
        ->keys('eso-date-picker:has(#demo-picker) button[data-day="2026-10-12"]', 'Escape')
        ->assertScript(opened('eso-date-picker:has(#demo-picker) [data-date-picker-content]'), false);
});

it('fills a month range from a preset', function () {
    $from = today()->startOfMonth()->subMonths(2)->format('Y-m');
    $to = today()->format('Y-m');

    visit('/controls')
        ->click('eso-date-picker:has(#demo-presets) [data-date-picker-trigger]')
        ->assertPresent('eso-date-picker:has(#demo-presets) [data-calendar-year]')
        ->click('eso-date-picker:has(#demo-presets) [data-date-picker-preset]:has-text("Last 3 months")')
        ->assertSeeIn('#period', "{$from} → {$to}");
});

it('binds a month from the grid and from the preset footer', function () {
    $picker = 'eso-date-picker:has(#demo-month-picker)';
    $footer = $picker.' [data-slot="date-picker-presets"]';
    $currentMonth = today()->toImmutable()->startOfMonth();
    $thisMonth = $currentMonth->format('Y-m');
    $lastMonth = $currentMonth->subMonth()->format('Y-m');
    $manualMonth = $currentMonth->subMonths(2);
    $disabledMonth = $currentMonth->addMonth();

    visit('/controls')
        ->click($picker.' [data-date-picker-trigger]')
        ->select($picker.' [data-calendar-year]', $disabledMonth->format('Y'))
        ->assertPresent($picker.' [data-date-picker-period="'.$disabledMonth->format('Y-m').'"]:disabled')
        ->select($picker.' [data-calendar-year]', $manualMonth->format('Y'))
        ->keys($picker.' [data-date-picker-period="'.$manualMonth->format('Y-m').'"]', 'Enter')
        ->assertSeeIn('#report-month', $manualMonth->format('Y-m'))
        ->click($footer.' button:has-text("This month")')
        ->assertSeeIn('#report-month', $thisMonth)
        ->click($footer.' button:has-text("Last month")')
        ->assertSeeIn('#report-month', $lastMonth)
        ->assertPresent($picker.' [data-date-picker-period="'.$lastMonth.'"][aria-pressed="true"]')
        ->assertNoJavaScriptErrors();
});

it('selects months across years and orders the bound range', function () {
    $picker = 'eso-date-picker:has(#demo-presets)';

    visit('/controls')
        ->click($picker.' [data-date-picker-trigger]')
        ->select($picker.' [data-calendar-year]', '2027')
        ->click($picker.' [data-date-picker-period="2027-02"]')
        ->click($picker.' [data-date-picker-previous-year]')
        ->click($picker.' [data-date-picker-period="2026-11"]')
        ->assertSeeIn('#period', '2026-11 → 2027-02')
        ->assertPresent($picker.' [data-date-picker-period="2026-12"][data-range-middle]')
        ->assertNoJavaScriptErrors();
});

it('binds the date with a complete time in ten-minute intervals', function () {
    visit('/controls')
        ->click('eso-date-picker:has(#demo-datetime) [data-date-picker-trigger]')
        ->click('eso-date-picker:has(#demo-datetime) [data-time-hour-toggle]')
        ->click('eso-date-picker:has(#demo-datetime) [data-time-hour-choice="18"]')
        ->assertSeeIn('eso-date-picker:has(#demo-datetime) [data-time-hour-label]', '18:00')
        ->assertSeeIn('#starts-at', '2026-10-29 10:30')
        ->assertNotPresent('eso-date-picker:has(#demo-datetime) [data-time-option="18:45"]')
        ->click('eso-date-picker:has(#demo-datetime) [data-time-option="18:40"]')
        ->assertSeeIn('#starts-at', '2026-10-29 18:40')
        ->click('eso-date-picker:has(#demo-datetime) button[data-day="2026-10-30"]')
        ->assertSeeIn('#starts-at', '2026-10-30 18:40');
});

it('binds separate start and end times for a date range', function () {
    $month = now()->format('Y-m');

    visit('/controls')
        ->click('eso-date-picker:has(#demo-shift) [data-date-picker-trigger]')
        ->click('eso-date-picker:has(#demo-shift) [data-date-picker-time="from"] [aria-label="Hour"] [data-time-option="8"]')
        ->click('eso-date-picker:has(#demo-shift) button[data-day="'.$month.'-08"]')
        ->click('eso-date-picker:has(#demo-shift) button[data-day="'.$month.'-09"]')
        ->click('eso-date-picker:has(#demo-shift) [data-date-picker-time="to"] [aria-label="Hour"] [data-time-option="17"]')
        ->assertSeeIn('#shift', $month.'-08 08:00 → '.$month.'-09 17:00');
});

it('selects seconds with the keyboard and dismisses the time picker with Escape', function () {
    $second = '[data-time-picker-content] [aria-label="Second"] [data-time-option="1"]';

    visit('/controls')
        ->assertSeeIn('#demo-time', '10:30:00')
        ->click('#demo-time')
        ->click('[data-time-picker-content] [role="tab"][data-value="second"]')
        ->keys('[data-time-picker-content] [aria-label="Second"] [data-time-option="0"]', 'ArrowRight')
        ->assertScript("document.activeElement.matches(".json_encode($second).")")
        ->keys($second, 'Enter')
        ->assertSeeIn('#time', '10:30:01')
        ->assertSeeIn('#demo-time', '10:30:01')
        ->keys($second, 'Escape')
        ->assertScript(opened('[data-time-picker-content]'), false)
        ->assertNoJavaScriptErrors();
});

it('selects one complete time in five-minute intervals', function () {
    $popup = 'eso-time-picker:has(#demo-time-slot) [data-time-picker-content]';

    visit('/controls')
        ->click('#demo-time-slot')
        ->assertNotPresent($popup.' select')
        ->assertNotPresent($popup.' button:has-text("Clear")')
        ->keys($popup.' [data-time-next]', 'Enter')
        ->assertSeeIn($popup.' [data-time-hour-label]', '11:00')
        ->assertSeeIn('#time-slot', '10:30')
        ->click($popup.' [data-time-previous]')
        ->assertSeeIn($popup.' [data-time-hour-label]', '10:00')
        ->keys($popup.' [data-time-hour-toggle]', 'Enter')
        ->assertScript('document.activeElement.dataset.timeHourChoice', '10')
        ->keys($popup.' [data-time-hour-choice="10"]', 'Escape')
        ->assertScript(opened($popup))
        ->assertAttribute($popup.' [data-time-hour-toggle]', 'aria-expanded', 'false')
        ->keys($popup.' [data-time-hour-toggle]', 'Enter')
        ->keys($popup.' [data-time-hour-choice="10"]', 'ArrowDown')
        ->assertScript('document.activeElement.dataset.timeHourChoice', '14')
        ->keys($popup.' [data-time-hour-choice="14"]', 'ArrowDown')
        ->assertScript('document.activeElement.dataset.timeHourChoice', '18')
        ->keys($popup.' [data-time-hour-choice="18"]', 'Enter')
        ->assertSeeIn($popup.' [data-time-hour-label]', '18:00')
        ->assertScript('document.activeElement.dataset.timeOption', '18:00')
        ->assertSeeIn('#time-slot', '10:30')
        ->assertPresent($popup.' [data-time-option="18:00"]')
        ->assertNotPresent($popup.' [data-time-option="18:01"]')
        ->click($popup.' [data-time-option="18:05"]')
        ->assertSeeIn('#time-slot', '18:05')
        ->assertSeeIn('#demo-time-slot', '18:05')
        ->assertScript(opened($popup), false)
        ->assertNoJavaScriptErrors();
});

it('shows a validation error inside a field', function () {
    visit('/controls')
        ->click('#birthday ~ * button[type="submit"], form[wire\\:submit="saveBirthday"] button[type="submit"]')
        ->assertSee('The birthday field is required.');
});
