@blaze

@props([
    'defaultValue' => null,
    'showError' => true,
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors, $showError);
@endphp

{{-- <ui-radio-group> exposes the checked radio as .value, so wire:model binds on the group itself. --}}
<ui-radio-group {{ $attributes->merge(['role' => 'radiogroup', 'data-slot' => 'radio-group', ...$state->attributes()]) }}>{{ $slot }}</ui-radio-group>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
