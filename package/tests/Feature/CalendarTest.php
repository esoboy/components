<?php

it('renders a calendar shell configured for the client', function () {
    app()->setLocale('zh_CN');

    $this->blade(<<<'BLADE'
        <x-ui::calendar mode="range" :default-value="['from' => '2026-01-05', 'to' => null]" default-month="2026-01"
            caption-layout="dropdown" :number-of-months="2" :show-outside-days="false" :week-starts-on="1"
            min="2025-01-01" max="2027-12-31" :disabled-dates="['2026-01-10']" class="rounded-lg border" />
        BLADE)
        ->assertSee('<ui-calendar data-slot="calendar" data-mode="range" data-value="{&quot;from&quot;:&quot;2026-01-05&quot;,&quot;to&quot;:null}" data-default-month="2026-01" data-caption-layout="dropdown" data-number-of-months="2" data-show-outside-days="false" data-week-starts-on="1" data-min="2025-01-01" data-max="2027-12-31" data-disabled-dates="[&quot;2026-01-10&quot;]" data-locale="zh-CN" data-label-previous="上个月" data-label-next="下个月" data-label-month="选择月份" data-label-year="选择年份" class="rounded-lg border" wire:ignore></ui-calendar>', false);
});

it('defaults to a single, labelled calendar', function () {
    $this->blade('<x-ui::calendar wire:model="date" />')
        ->assertSee('data-mode="single"', false)
        ->assertSee('data-caption-layout="label"', false)
        ->assertSee('data-show-outside-days="true"', false)
        ->assertDontSee('data-value', false)
        ->assertSee('wire:model="date"', false);
});

it('renders a date picker around a popover and a calendar', function () {
    $html = (string) $this->blade('<x-ui::date-picker wire:model="date" placeholder="Pick a date" default-value="2026-01-05" mode="single" class="w-64" />');

    [$id] = idsOf($html, 'commandfor');

    expect($html)
        ->toContain('<ui-date-picker data-slot="date-picker" data-mode="single" data-placeholder="Pick a date" data-locale="en" wire:model="date">')
        ->toContain("data-variant=\"outline\" data-size=\"default\" commandfor=\"{$id}\" command=\"toggle-popover\" data-date-picker-trigger=\"data-date-picker-trigger\"")
        ->toContain('class="w-64"')
        ->toContain('<span data-slot="date-picker-value">Pick a date</span>')
        ->toContain('data-date-picker-content')
        ->toContain('<ui-calendar data-slot="calendar" data-mode="single" data-value="&quot;2026-01-05&quot;"');
});

it('leads a range picker with a calendar icon and shows two months', function () {
    $html = (string) $this->blade('<x-ui::date-picker mode="range" placeholder="Pick a range" />');

    expect($html)
        ->toMatch('/data-date-picker-trigger="data-date-picker-trigger">\s*<svg data-icon="inline-start"/')
        ->not->toContain('data-icon="inline-end"')
        ->toContain('data-number-of-months="2"');
});

it('renders presets under the calendar as calendar values', function () {
    $html = (string) $this->blade(<<<'BLADE'
        <x-ui::date-picker mode="range" :presets="['Last 7 days' => [\Illuminate\Support\Carbon::parse('2026-10-21'), '2026-10-27 12:00:00'], 'Fixed' => ['from' => '2026-01-01', 'to' => '2026-01-31']]" />
        <x-ui::date-picker :presets="['Today' => \Illuminate\Support\Carbon::parse('2026-10-27')]" />
        BLADE);

    expect($html)
        ->toContain('<div data-slot="date-picker-presets">')
        ->toContain('data-variant="outline" data-size="sm" data-date-picker-preset="{&quot;from&quot;:&quot;2026-10-21&quot;,&quot;to&quot;:&quot;2026-10-27&quot;}">Last 7 days</button>')
        ->toContain('data-date-picker-preset="{&quot;from&quot;:&quot;2026-01-01&quot;,&quot;to&quot;:&quot;2026-01-31&quot;}">Fixed</button>')
        ->toContain('data-date-picker-preset="&quot;2026-10-27&quot;">Today</button>');
});

it('renders a time picker as a time input', function () {
    $this->blade('<x-ui::time-picker wire:model="time" step="1" />')
        ->assertSee('<input type="time" data-slot="input" data-time-picker="data-time-picker" wire:model="time" step="1">', false);
});

it('validates a date picker inside a field', function () {
    $this->withViewErrors(['date' => 'Pick a date.'])
        ->blade('<x-ui::field><x-ui::date-picker id="date" wire:model="date" /></x-ui::field>')
        ->assertSee('aria-invalid="true" aria-describedby="date-error"', false)
        ->assertSeeInOrder(['</ui-date-picker>', 'Pick a date.'], false);
});
