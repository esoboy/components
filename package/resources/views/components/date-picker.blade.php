@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'mode' => 'single',
    'placeholder' => null,
    'defaultValue' => null,
    'defaultMonth' => null,
    'captionLayout' => 'label',
    'numberOfMonths' => 1,
    'weekStartsOn' => null,
    'min' => null,
    'max' => null,
    'disabledDates' => [],
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
@endphp

{{-- shadcn's date picker example as one control: wire:model binds on the picker, class styles the trigger. --}}
<ui-date-picker {{ $attributes->except(['class', 'style'])->merge(['data-slot' => 'date-picker', 'data-placeholder' => $placeholder, 'data-locale' => str_replace('_', '-', app()->getLocale()), ...$state->attributes()]) }}>
    <x-ui::popover>
        <x-ui::popover.trigger variant="outline" data-date-picker-trigger :attributes="$attributes->only(['class', 'style'])">
            <span data-slot="date-picker-value">{{ $placeholder }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </x-ui::popover.trigger>
        <x-ui::popover.content align="start" data-date-picker-content>
            <x-ui::calendar
                :mode="$mode"
                :default-value="$value"
                :default-month="$defaultMonth"
                :caption-layout="$captionLayout"
                :number-of-months="$numberOfMonths"
                :week-starts-on="$weekStartsOn"
                :min="$min"
                :max="$max"
                :disabled-dates="$disabledDates"
            />
        </x-ui::popover.content>
    </x-ui::popover>
</ui-date-picker>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
