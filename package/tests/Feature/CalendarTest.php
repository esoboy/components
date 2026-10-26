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
        ->toContain('<ui-date-picker data-slot="date-picker" data-placeholder="Pick a date" data-locale="en" wire:model="date">')
        ->toContain("data-variant=\"outline\" data-size=\"default\" commandfor=\"{$id}\" command=\"toggle-popover\" data-date-picker-trigger=\"data-date-picker-trigger\"")
        ->toContain('class="w-64"')
        ->toContain('<span data-slot="date-picker-value">Pick a date</span>')
        ->toContain('data-date-picker-content')
        ->toContain('<ui-calendar data-slot="calendar" data-mode="single" data-value="&quot;2026-01-05&quot;"');
});

it('validates a date picker inside a field', function () {
    $this->withViewErrors(['date' => 'Pick a date.'])
        ->blade('<x-ui::field><x-ui::date-picker id="date" wire:model="date" /></x-ui::field>')
        ->assertSee('aria-invalid="true" aria-describedby="date-error"', false)
        ->assertSeeInOrder(['</ui-date-picker>', 'Pick a date.'], false);
});
