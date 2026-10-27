@blaze

@props([
    'mode' => 'single',
    'defaultValue' => null,
    'defaultMonth' => null,
    'captionLayout' => 'label',
    'numberOfMonths' => 1,
    'showOutsideDays' => true,
    'weekStartsOn' => null,
    'min' => null,
    'max' => null,
    'disabledDates' => [],
    'startMonth' => null,
    'endMonth' => null,
])

@php
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
@endphp

{{-- <eso-calendar> renders the month grid in the app's locale and exposes the selection as .value (Y-m-d). --}}
<eso-calendar {{ $attributes->merge([
    'data-slot' => 'calendar',
    'data-mode' => $mode,
    'data-value' => $value === null ? false : json_encode($value),
    'data-default-month' => $defaultMonth,
    'data-caption-layout' => $captionLayout,
    'data-number-of-months' => $numberOfMonths,
    'data-show-outside-days' => $showOutsideDays ? 'true' : 'false',
    'data-week-starts-on' => $weekStartsOn,
    'data-min' => $min,
    'data-max' => $max,
    'data-disabled-dates' => $disabledDates ? json_encode(array_values((array) $disabledDates)) : false,
    'data-start-month' => $startMonth,
    'data-end-month' => $endMonth,
    'data-locale' => str_replace('_', '-', app()->getLocale()),
    'data-label-previous' => __('ui.calendar.previous'),
    'data-label-next' => __('ui.calendar.next'),
    'data-label-month' => __('ui.calendar.month'),
    'data-label-year' => __('ui.calendar.year'),
]) }} wire:ignore></eso-calendar>
