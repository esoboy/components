@blaze

@use('Esoboy\Ui\Support\ControlState')
@use('Esoboy\Ui\Support\Model')

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'defaultValue' => null,
    'multiple' => false,
    'shouldFilter' => true,
])

@php
$state = ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? Model::value($attributes);
@endphp

{{-- <eso-combobox> exposes the chosen item (or items) as .value, so wire:model binds on the combobox itself. --}}
<eso-combobox {{ $attributes->except('name')->merge([
    'data-slot' => 'combobox',
    'data-multiple' => (bool) $multiple,
    'data-should-filter' => $shouldFilter ? 'true' : 'false',
    'data-value' => $value === null ? false : json_encode($value),
    'data-name' => $attributes->get('name') ?? false,
    ...$state->attributes(),
]) }}>{{ $slot }}</eso-combobox>
<x-ui::field.error :state="$state" />
