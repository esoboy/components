@blaze

@aware([
    'uiField' => false,
    'showError' => true,
])

@props([
    'size' => 'default',
])

@php
$state = \Esoboy\Ui\Support\ControlState::for($attributes, $errors ?? null, $uiField, $showError);
@endphp

<input {{ $attributes->merge(['type' => 'checkbox', 'role' => 'switch', 'data-slot' => 'switch', 'data-size' => $size, ...$state->attributes()]) }}>
@if ($state->showsError())
<x-ui::field.error :id="$state->errorId" :messages="$state->messages" />
@endif
