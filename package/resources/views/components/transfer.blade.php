@blaze

@use('Esoboy\Ui\Support\Options')
@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- Not in shadcn: two lists of Checkboxes with Command's search and items; the value is the right list. --}}
@props([
    'options' => [],
    'titles' => [],
    'searchable' => false,
    'disabled' => false,
    'value' => null,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = Model::value($attributes, $value) ?? [];
$validation = $state->attributes();
$settings = compact('titles', 'searchable', 'disabled', 'validation');
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'factory-'.\Esoboy\Ui\Support\Sequence::next('transfer');
@endphp

@livewire('ui.transfer', ['options' => $options, 'settings' => $settings, 'value' => $value, ...$attributes->except('wire:key')->getAttributes()], key('ui-transfer-'.$key))
<x-ui::field.error :state="$state" />
<x-ui::extras />
