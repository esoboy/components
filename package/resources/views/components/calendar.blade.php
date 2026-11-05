@blaze

@use('Esoboy\Ui\Support\Model')

@props([
    'mode' => 'single',
    'disabled' => false,
    'value' => null,
    'month' => null,
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
$value = Model::value($attributes, $value);
$settings = compact('mode', 'disabled', 'month', 'captionLayout', 'numberOfMonths', 'showOutsideDays', 'weekStartsOn', 'min', 'max', 'disabledDates', 'startMonth', 'endMonth');
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'calendar-'.\Esoboy\Ui\Support\Sequence::next('calendar');
@endphp

@livewire('ui.calendar', ['settings' => $settings, 'value' => $value, ...$attributes->except('wire:key')->getAttributes()], key('ui-calendar-'.$key))
