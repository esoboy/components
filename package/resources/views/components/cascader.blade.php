@blaze

@use('Esoboy\Ui\Support\Options')
@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')

@aware([
    'uiField' => false,
    'showError' => true,
])

{{-- options: [['value' => ..., 'label' => ..., 'children' => [...], 'disabled' => bool], ...]. The value is the path of values. --}}
@props([
    'options' => [],
    'placeholder' => null,
    'separator' => '/',
    'changeOnSelect' => false,
    'size' => 'default',
    'disabled' => false,
    'value' => null,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = Model::value($attributes, $value) ?? [];
$validation = $state->attributes();
$settings = compact('placeholder', 'separator', 'changeOnSelect', 'size', 'disabled', 'validation');
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'cascader-'.\Esoboy\Ui\Support\Sequence::next('cascader');
@endphp

@livewire('ui.cascader', ['options' => $options, 'settings' => $settings, 'value' => $value, ...$attributes->except('wire:key')->getAttributes()], key('ui-cascader-'.$key))
<x-ui::field.error :state="$state" />
<x-ui::extras />
