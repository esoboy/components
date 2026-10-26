@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'defaultValue' => null,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
$value = $defaultValue ?? \Esoboy\Ui\Support\Model::value($attributes);
@endphp

{{-- <ui-select> exposes the chosen item as .value, so wire:model binds on the select itself. --}}
<ui-select {{ $attributes->except('name')->merge(['data-slot' => 'select', 'data-value' => is_scalar($value) ? (string) $value : false, ...$state->attributes()]) }}>
    {{ $slot }}
    @if ($attributes->has('name'))
        <input type="hidden" name="{{ $attributes->get('name') }}" value="{{ is_scalar($value) ? $value : '' }}">
    @endif
</ui-select>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
