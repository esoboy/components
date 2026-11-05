@blaze
@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')
@aware(['uiField' => false, 'showError' => true])
@props(['options' => [], 'value' => null, 'multiple' => false, 'autocomplete' => false, 'filter' => true, 'placeholder' => null, 'clearable' => false, 'trigger' => true, 'button' => false, 'disabled' => false, 'empty' => null])
@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = Model::value($attributes, $value);
$validation = $state->attributes();
$settings = compact('multiple', 'autocomplete', 'filter', 'placeholder', 'clearable', 'trigger', 'button', 'disabled', 'empty', 'validation');
$key = $attributes->get('wire:key') ?? $attributes->get('id') ?? Model::key($attributes) ?? 'combobox-'.\Esoboy\Ui\Support\Sequence::next('combobox');
@endphp
@livewire('ui.combobox', ['options' => $options, 'settings' => $settings, 'value' => $value, ...$attributes->except('wire:key')->getAttributes()], key('ui-combobox-'.$key))
<x-ui::field.error :state="$state" />
