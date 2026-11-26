<?php

use Carbon\CarbonImmutable;
use Dom\HTMLDocument;
use Esoboy\Ui\Livewire\Calendar;
use Esoboy\Ui\Livewire\DatePicker;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

it('submits the complete date value through native form fields', function (string $component, array|string $value, bool $range) {
    $html = Blade::render('<x-ui::'.$component.' id="period" name="period" :initial-value="$value" :range="$range" />', compact('value', 'range'));
    $fields = HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('select[name] option[selected]');
    $pairs = [];
    foreach ($fields as $field) {
        $pairs[] = urlencode($field->parentNode->getAttribute('name')).'='.urlencode($field->getAttribute('value'));
    }
    parse_str(implode('&', $pairs), $submitted);

    expect($submitted)->toBe(['period' => $value]);
})->with(['calendar', 'date-picker'])->with([
    'single day' => ['2026-11-18', false],
    'first endpoint' => [['2026-11-18'], true],
    'both endpoints' => [['2026-11-18', '2026-11-20'], true],
]);

it('opens one-sided calendars inside their navigation window', function (string $component, string $caption) {
    CarbonImmutable::setTestNow('2026-11-19');
    try {
        Livewire::test($component, ['captionLayout' => $caption, 'min' => '2027-06-12'])
            ->assertSet('month', '2027-06-01')
            ->call('moveMonth', -1)->assertSet('month', '2027-06-01')
            ->call('moveMonth', 1)->assertSet('month', '2027-07-01')->assertSet('value', null);
        Livewire::test($component, ['captionLayout' => $caption, 'max' => '1899-05-15'])
            ->assertSet('month', '1899-05-01')
            ->call('moveMonth', 1)->assertSet('month', '1899-05-01')
            ->call('moveMonth', -1)->assertSet('month', '1899-04-01');
    } finally {
        CarbonImmutable::setTestNow();
    }
})->with([[Calendar::class, 'label'], [DatePicker::class, 'dropdown']]);

it('starts a fresh range when its first day is no longer selectable', function (string $component, array $limits, string $start, string $day) {
    $props = ['range' => true, 'name' => 'period', ...$limits];
    $calendar = Livewire::test($component, [...$props, 'initialValue' => [$start]])
        ->call('choose', $day)->assertSet('value', [$day])->assertDispatched('value-changed', value: [$day]);
    $fields = HTMLDocument::createFromString($calendar->html(), LIBXML_NOERROR)->querySelectorAll('select[name="period[]"] option[value="'.$day.'"]');
    expect($fields->length)->toBe(1)->and($fields->item(0)->getAttribute('value'))->toBe($day);
    $calendar->call('choose', $day)->assertSet('value', [$day, $day]);

    Livewire::test($component, [...$props, 'mode' => 'controlled', 'externalValue' => [$start]])
        ->call('choose', $day)->assertDispatched('value-changed', value: [$day])->assertSet('externalValue', [$start]);
})->with(['calendar' => [Calendar::class], 'date picker' => [DatePicker::class]])->with([
    'later minimum' => [['min' => '2027-09-10'], '2027-07-12', '2027-09-15'],
    'earlier maximum' => [['max' => '2027-07-31'], '2027-09-15', '2027-07-12'],
    'disabled first day' => [['disabledDates' => ['2027-07-12']], '2027-07-12', '2027-07-14'],
]);

it('uses the same two-month window for the caption and the navigation arrows', function () {
    $calendar = Livewire::test(Calendar::class, [
        'captionLayout' => 'dropdown', 'numberOfMonths' => 2, 'initialMonth' => '2028-01-01',
        'min' => '2027-10-10', 'max' => '2028-01-18',
    ])->assertSet('month', '2027-12-01')
        ->call('moveMonth', 1)->assertSet('month', '2027-12-01')
        ->call('showMonth', '2028-01')->assertSet('month', '2027-12-01')
        ->call('showMonthOfYear', 11)->assertSet('month', '2027-11-01');

    expect(array_column($calendar->instance()->years(), 'value'))->toBe([2027])
        ->and($calendar->instance()->months()[0]['disabled'])->toBeTrue()
        ->and($calendar->instance()->months()[11]['disabled'])->toBeFalse();
});

it('uses native caption selects to navigate years and months without selecting a date', function () {
    Livewire::test(Calendar::class, ['captionLayout' => 'dropdown', 'initialValue' => '2026-11-17', 'endMonth' => '2030-12-01'])
        ->assertSee('Select year')->assertSee('Select month')
        ->assertSee('<select', false)
        ->call('showYear', 2027)->assertSet('month', '2027-11-01')
        ->call('showMonthOfYear', 1)->assertSet('month', '2027-01-01')
        ->assertSet('value', '2026-11-17');
});

it('clamps year navigation to date boundaries and rejects out of range months', function () {
    Livewire::test(Calendar::class, ['captionLayout' => 'dropdown', 'initialValue' => '2026-11-17', 'min' => '2025-06-15', 'max' => '2027-03-10'])
        ->call('showYear', 2027)->assertSet('month', '2027-03-01')
        ->call('showMonthOfYear', 4)->assertSet('month', '2027-03-01')
        ->call('showYear', 2025)->assertSet('month', '2025-06-01')
        ->call('showMonthOfYear', 0)->assertSet('month', '2025-06-01')
        ->call('showYear', 2024)->assertSet('month', '2025-06-01')
        ->assertSet('value', '2026-11-17');
});

it('does not navigate a disabled calendar through its caption selects', function () {
    Livewire::test(Calendar::class, ['captionLayout' => 'dropdown', 'initialValue' => '2026-11-17', 'disabled' => true])
        ->call('showYear', 2027)->call('showMonthOfYear', 1)
        ->assertSet('month', '2026-11-01')->assertSet('value', '2026-11-17');
});

it('bounds dropdown navigation to the current year unless a future end month is supplied', function () {
    CarbonImmutable::setTestNow('2026-11-17');

    try {
        $calendar = Livewire::test(Calendar::class, ['captionLayout' => 'dropdown', 'initialValue' => '2026-12-15'])
            ->call('moveMonth', 1)->assertSet('month', '2026-12-01')
            ->call('showYear', 2027)->assertSet('month', '2026-12-01');

        expect(array_column($calendar->instance()->years(), 'value'))->toHaveCount(101)->toContain(1926, 2026)->not->toContain(2027);
        expect(collect($calendar->instance()->monthDays(0))->firstWhere('date', '2027-01-01')['hidden'])->toBeTrue();

        Livewire::test(Calendar::class, ['captionLayout' => 'dropdown', 'initialValue' => '2026-12-15', 'endMonth' => '2028-12-01'])
            ->call('moveMonth', 1)->assertSet('month', '2027-01-01')
            ->assertSet('value', '2026-12-15');
    } finally {
        CarbonImmutable::setTestNow();
    }
});
