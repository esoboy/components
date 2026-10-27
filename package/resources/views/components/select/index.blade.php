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

{{-- <eso-select> exposes the chosen item as .value, so wire:model binds on the select itself. --}}
<eso-select {{ $attributes->except('name')->merge(['data-slot' => 'select', 'data-value' => is_scalar($value) ? (string) $value : false, 'data-name' => $attributes->get('name') ?? false, ...$state->attributes()]) }}>
    {{ $slot }}
    @if ($attributes->has('name'))
        <input type="hidden" name="{{ $attributes->get('name') }}" value="{{ is_scalar($value) ? $value : '' }}">
    @endif
</eso-select>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
