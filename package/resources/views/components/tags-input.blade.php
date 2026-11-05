@blaze

@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- Enter or a comma adds what was typed, Backspace in the empty input removes the last tag. --}}
@props([
    'placeholder' => null,
    'max' => null,
    'disabled' => false,
    'value' => null,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = Model::value($attributes, $value) ?? [];
$validation = $state->attributes();
$settings = compact('placeholder', 'max', 'disabled', 'validation');
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'factory-'.\Esoboy\Ui\Support\Sequence::next('tags-input');
@endphp

@livewire('ui.tags-input', ['settings' => $settings, 'value' => $value, ...$attributes->except('wire:key')->getAttributes()], key('ui-tags-input-'.$key))
<x-ui::field.error :state="$state" />
<x-ui::extras />
