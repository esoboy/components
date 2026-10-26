@blaze

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
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
@endphp

{{-- <ui-combobox> exposes the chosen item (or items) as .value, so wire:model binds on the combobox itself. --}}
<ui-combobox {{ $attributes->merge([
    'data-slot' => 'combobox',
    'data-multiple' => (bool) $multiple,
    'data-should-filter' => $shouldFilter ? 'true' : 'false',
    'data-value' => $value === null ? false : json_encode($value),
    ...$state->attributes(),
]) }}>{{ $slot }}</ui-combobox>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
