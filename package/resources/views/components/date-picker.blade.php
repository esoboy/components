@blaze

@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\DatePresets')
@use('Esoboy\Ui\Support\Model')

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'mode' => 'single',
    'disabled' => false,
    'placeholder' => null,
    'value' => null,
    'month' => null,
    'captionLayout' => 'label',
    'numberOfMonths' => null,
    'weekStartsOn' => null,
    'min' => null,
    'max' => null,
    'disabledDates' => [],
    'presets' => [],
    'time' => false,
    'step' => null,
    'timeValue' => '00:00',
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = Model::value($attributes, $value);
$numberOfMonths ??= $mode === 'range' ? 2 : 1;
$presets = DatePresets::normalize($presets, $mode);
$validation = $state->attributes();
$settings = compact('mode', 'disabled', 'placeholder', 'month', 'captionLayout', 'numberOfMonths', 'weekStartsOn', 'min', 'max', 'disabledDates', 'presets', 'time', 'step', 'timeValue', 'validation');
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'picker-'.\Esoboy\Ui\Support\Sequence::next('date-picker');
@endphp

@livewire('ui.date-picker', ['settings' => $settings, 'value' => $value, ...$attributes->except('wire:key')->getAttributes()], key('ui-date-picker-'.$key))
<x-ui::field.error :state="$state" />
