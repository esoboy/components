<?php

use Carbon\Carbon;
use Esoboy\Ui\Livewire\DatePicker;
use Esoboy\Ui\Support\DatePresets;
use Livewire\Livewire;

it('renders localized calendar dates and constraints in PHP', function () {
    app()->setLocale('zh_CN');
    $html = (string) $this->blade('<x-ui::calendar mode="range" month="2026-01" :value="[\'from\' => \'2026-01-05\', \'to\' => null]" caption-layout="dropdown" :number-of-months="2" :show-outside-days="false" :week-starts-on="1" min="2025-01-01" max="2027-12-31" :disabled-dates="[\'2026-01-10\']" class="rounded-lg border" />');
    expect($html)->toContainMarkup('<eso-calendar data-slot="calendar" data-mode="range" class="rounded-lg border">')
        ->toContain('data-day="2026-01-05"')->toContain('data-calendar-previous')->toContain('选择月份')->toContain('data-hidden="true"')
        ->not->toContain('<template')->and(substr_count($html, 'data-slot="calendar-month"'))->toBe(2);
});

it('renders a labelled single calendar without client date generation', function () {
    $this->blade('<x-ui::calendar value="2026-01-05" />')->assertSee('data-mode="single"', false)->assertSee('January 2026')->assertSee('data-day="2026-01-05"', false);
});

it('renders the selected picker label and popover on the server', function () {
    $html = (string) $this->blade('<x-ui::date-picker value="2026-01-05" placeholder="Pick a date" class="w-64" name="date" />');
    expect($html)->toContainMarkup('<span data-slot="date-picker-value">January 5, 2026</span>')->toContain('data-date-picker-content')->toContain('class="w-64"')->toContainMarkup('<input type="hidden" name="date" value="2026-01-05">');
});

it('leads a range picker with the calendar icon and renders two months', function () {
    $html = (string) $this->blade('<x-ui::date-picker mode="range" month="2026-01" placeholder="Pick a range" />');
    expect($html)->toContain('data-icon="inline-start"')->not->toContain('data-icon="inline-end"')->and(substr_count($html, 'data-slot="calendar-month"'))->toBe(2);
});

it('normalizes Carbon presets and selects them in PHP', function () {
    $presets = DatePresets::normalize(['Week' => [Carbon::parse('2026-10-21'), '2026-10-27 12:00:00']], 'range');
    expect($presets)->toBe(['Week' => ['from' => '2026-10-21', 'to' => '2026-10-27']]);
    Livewire::test(DatePicker::class, ['settings' => ['presets' => $presets, 'mode' => 'range']])->call('preset', 'Week')->assertSet('value', $presets['Week']);
});

it('renders date and time inputs with PHP generated labels', function () {
    $html = (string) $this->blade('<x-ui::date-picker time value="2026-01-05 14:30" />');
    expect($html)->toContain('data-slot="date-picker-time"')->toContainMarkup('<input type="time" value="14:30" data-date-picker-time="from">')->not->toContain('data-date-picker-time="to"');
});

it('keeps separate times at each end of a range', function () {
    app()->setLocale('zh_CN');
    $html = (string) $this->blade('<x-ui::date-picker mode="range" time step="1" time-value="09:00" :value="[\'from\' => \'2026-01-05 08:00:00\', \'to\' => null]" />');
    expect($html)->toContain('开始时间')->toContain('结束时间')->toContainMarkup('<input type="time" step="1" value="08:00:00" data-date-picker-time="from">')->toContainMarkup('<input type="time" step="1" value="09:00" data-date-picker-time="to">');
});

it('renders a time picker as a native time input', function () {
    $this->blade('<x-ui::time-picker wire:model="time" step="1" />')->assertSeeMarkup('<input type="time" data-slot="input" data-time-picker="data-time-picker" wire:model="time" step="1">');
});

it('keeps the picker validation error with its outer field', function () {
    $this->withViewErrors(['date' => 'Pick a date.'])->blade('<x-ui::field><x-ui::date-picker id="date" wire:model="date" /></x-ui::field>')->assertSee('aria-invalid="true"', false)->assertSee('aria-describedby="date-error"', false)->assertSeeMarkupInOrder(['</eso-date-picker>', 'Pick a date.']);
});
