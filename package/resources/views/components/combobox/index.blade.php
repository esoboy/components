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

{{-- <eso-combobox> exposes the chosen item (or items) as .value, so wire:model binds on the combobox itself. --}}
<eso-combobox {{ $attributes->except('name')->merge([
    'data-slot' => 'combobox',
    'data-multiple' => (bool) $multiple,
    'data-should-filter' => $shouldFilter ? 'true' : 'false',
    'data-value' => $value === null ? false : json_encode($value),
    'data-name' => $attributes->get('name') ?? false,
    ...$state->attributes(),
]) }}>{{ $slot }}</eso-combobox>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
