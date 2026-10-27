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
    'numberOfMonths' => null,
    'weekStartsOn' => null,
    'min' => null,
    'max' => null,
    'disabledDates' => [],
    'presets' => [],
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
$range = $mode === 'range';
$presets = \Esoboy\Ui\Support\DatePresets::normalize($presets, $mode);
@endphp

{{-- shadcn's date picker examples as one control: wire:model binds on the picker, class styles the trigger. --}}
<ui-date-picker {{ $attributes->except(['class', 'style'])->merge(['data-slot' => 'date-picker', 'data-mode' => $mode, 'data-placeholder' => $placeholder, 'data-locale' => str_replace('_', '-', app()->getLocale()), ...$state->attributes()]) }}>
    <x-ui::popover>
        <x-ui::popover.trigger variant="outline" data-date-picker-trigger :attributes="$attributes->only(['class', 'style'])">
            @if ($range)
            <svg data-icon="inline-start" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/></svg>
            @endif
            <span data-slot="date-picker-value">{{ $placeholder }}</span>
            @unless ($range)
            <svg data-icon="inline-end" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
            @endunless
        </x-ui::popover.trigger>
        <x-ui::popover.content align="start" data-date-picker-content>
            <x-ui::calendar
                :mode="$mode"
                :default-value="$value"
                :default-month="$defaultMonth"
                :caption-layout="$captionLayout"
                :number-of-months="$numberOfMonths ?? ($range ? 2 : 1)"
                :week-starts-on="$weekStartsOn"
                :min="$min"
                :max="$max"
                :disabled-dates="$disabledDates"
            />
            @if ($presets)
            {{-- As in shadcn's Calendar presets example. --}}
            <div data-slot="date-picker-presets">
                @foreach ($presets as $label => $preset)
                <x-ui::button variant="outline" size="sm" :data-date-picker-preset="json_encode($preset)">{{ $label }}</x-ui::button>
                @endforeach
            </div>
            @endif
        </x-ui::popover.content>
    </x-ui::popover>
</ui-date-picker>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
